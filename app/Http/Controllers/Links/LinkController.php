<?php

namespace App\Http\Controllers\Links;

use App\Enums\LinkType;
use App\Events\LinkCreated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Links\StoreLinkRequest;
use App\Models\Domain;
use App\Models\Link;
use App\Models\LinkAccessToken;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class LinkController extends Controller
{
    public function create(Request $request): Response
    {
        $domains = Domain::query()
            ->where('status', Domain::STATUS_VERIFIED)
            ->where(function ($query) use ($request) {
                $query->where('type', Domain::TYPE_PLATFORM);

                if ($request->user()) {
                    $query->orWhere(function ($nested) use ($request) {
                        $nested->where('type', Domain::TYPE_CUSTOM)
                            ->where('user_id', $request->user()->id);
                    });
                }
            })
            ->orderBy('type')
            ->orderBy('hostname')
            ->get(['id', 'hostname', 'type', 'redirection_id']);

        return Inertia::render('links/Create', [
            'domains' => $domains,
            'guestTtlDays' => config('links.guest_ttl_days'),
            'isGuest' => $request->user() === null,
            'turnstileSiteKey' => config('services.turnstile.site_key'),
        ]);
    }

    public function store(StoreLinkRequest $request): RedirectResponse
    {
        $linkType = LinkType::tryFrom($request->string('link_type')->toString()) ?? LinkType::Static;
        $domain = $this->resolveDomainForCreate($request);
        $isRootRedirect = $request->boolean('root_redirect');
        $alias = $this->normalizeAlias($request->string('alias')->toString());

        if ($isRootRedirect) {
            if ($domain->type !== Domain::TYPE_CUSTOM) {
                throw ValidationException::withMessages([
                    'root_redirect' => 'Root domain redirects are only available on custom domains.',
                ]);
            }

            if ($domain->redirection_id !== null) {
                throw ValidationException::withMessages([
                    'root_redirect' => 'This domain already has a root redirect.',
                ]);
            }

            $alias = null;
        }

        if ($domain->type === Domain::TYPE_PLATFORM && $alias !== null) {
            throw ValidationException::withMessages([
                'alias' => 'Custom aliases are only available on verified custom domains.',
            ]);
        }

        if ($alias !== null && $this->aliasExists($domain, $alias)) {
            throw ValidationException::withMessages([
                'alias' => 'That alias is already taken on this domain.',
            ]);
        }

        $expiresAt = $this->resolveExpiry($request);
        $code = $this->generateCode($domain);
        $fallbackDestination = $this->normalizeDestination($request->string('fallback_destination_url')->toString());
        $destinationUrl = $this->normalizeDestination($request->string('destination_url')->toString());

        if ($linkType === LinkType::Dynamic) {
            $destinationUrl = $fallbackDestination;
        }

        $link = DB::transaction(function () use (
            $request,
            $domain,
            $code,
            $alias,
            $linkType,
            $destinationUrl,
            $fallbackDestination,
            $expiresAt,
            $isRootRedirect,
        ): Link {
            $link = Link::query()->create([
                'domain_id' => $domain->id,
                'user_id' => optional($request->user())->id,
                'code' => $code,
                'alias' => $alias,
                'link_type' => $linkType,
                'destination_url' => $destinationUrl,
                'fallback_destination_url' => $fallbackDestination,
                'password_hash' => $this->hashPassword($request->string('password')->toString()),
                'expires_at' => $expiresAt,
                'click_count' => 0,
                'last_accessed_at' => null,
                'qr_path' => null,
            ]);

            if ($linkType === LinkType::Dynamic) {
                $this->storeDynamicRules($request, $link);
            }

            if ($isRootRedirect) {
                $domain->forceFill([
                    'redirection_id' => $link->id,
                ])->save();
            }

            return $link;
        });

        event(new LinkCreated($link));

        $tokenExpiresAt = $request->user()
            ? null
            : now()->addDays(config('links.guest_ttl_days'));

        $accessToken = LinkAccessToken::query()->create([
            'link_id' => $link->id,
            'token' => Str::random(48),
            'expires_at' => $tokenExpiresAt,
        ]);

        return to_route('links.success', ['token' => $accessToken->token]);
    }

    public function success(Request $request, string $token): Response
    {
        $accessToken = LinkAccessToken::query()
            ->where('token', $token)
            ->firstOrFail();

        if ($accessToken->expires_at && $accessToken->expires_at->isPast()) {
            abort(410);
        }

        $link = $accessToken->link()->with('domain')->firstOrFail();
        $shortUrl = $this->shortUrl($request, $link);

        return Inertia::render('links/Success', [
            'shortUrl' => $shortUrl,
            'destinationUrl' => $link->destination_url,
            'expiresAt' => optional($link->expires_at)->toIso8601String(),
            'passwordProtected' => $link->password_hash !== null,
            'qrReady' => $link->qr_path !== null,
            'qrChannel' => sprintf('link-qr.%s', $accessToken->token),
            'qrPreviewUrl' => $link->qr_path
                ? route('links.qr.guest', ['token' => $accessToken->token])
                : null,
            'qrDownloadUrl' => $link->qr_path
                ? route('links.qr.guest', [
                    'token' => $accessToken->token,
                    'download' => 1,
                ])
                : null,
            'qrPngDownloadUrl' => $link->qr_path
                ? route('links.qr.guest', [
                    'token' => $accessToken->token,
                    'download' => 1,
                    'format' => 'png',
                    'w' => 1024,
                ])
                : null,
        ]);
    }

    private function resolveDomainForCreate(StoreLinkRequest $request): Domain
    {
        $domain = Domain::query()->find($request->integer('domain_id'));

        if (! $domain) {
            throw ValidationException::withMessages([
                'domain_id' => 'Choose a valid domain.',
            ]);
        }

        if ($domain->status !== Domain::STATUS_VERIFIED) {
            throw ValidationException::withMessages([
                'domain_id' => 'This domain is not verified yet.',
            ]);
        }

        if (! $request->user()) {
            if ($domain->type !== Domain::TYPE_PLATFORM) {
                throw ValidationException::withMessages([
                    'domain_id' => 'Guests can only use platform domains.',
                ]);
            }

            return $domain;
        }

        if ($domain->type === Domain::TYPE_CUSTOM && $domain->user_id !== $request->user()->id) {
            throw ValidationException::withMessages([
                'domain_id' => 'Choose one of your verified domains.',
            ]);
        }

        return $domain;
    }

    private function resolveExpiry(StoreLinkRequest $request): ?\Carbon\CarbonInterface
    {
        if (! $request->user()) {
            return now()->addDays(config('links.guest_ttl_days'));
        }

        $expiresInput = $request->input('expires_at');

        if (is_string($expiresInput) && $expiresInput !== '') {
            return Date::parse($expiresInput);
        }

        return null;
    }

    private function normalizeAlias(string $alias): ?string
    {
        $normalized = trim($alias);

        if ($normalized === '') {
            return null;
        }

        return Str::lower($normalized);
    }

    private function normalizeDestination(string $destination): ?string
    {
        $normalized = trim($destination);

        if ($normalized === '') {
            return null;
        }

        return $normalized;
    }

    private function aliasExists(Domain $domain, string $alias): bool
    {
        return Link::query()
            ->where('domain_id', $domain->id)
            ->where('alias', $alias)
            ->exists();
    }

    private function generateCode(Domain $domain): string
    {
        $attempts = 0;

        while ($attempts < 8) {
            $attempts++;
            $code = Str::lower(Str::random(7));

            $exists = Link::query()
                ->where('domain_id', $domain->id)
                ->where('code', $code)
                ->exists();

            if (! $exists) {
                return $code;
            }
        }

        throw ValidationException::withMessages([
            'domain_id' => 'Unable to generate a unique short code.',
        ]);
    }

    private function hashPassword(string $password): ?string
    {
        $trimmed = trim($password);

        if ($trimmed === '') {
            return null;
        }

        return Hash::make($trimmed);
    }

    private function storeDynamicRules(StoreLinkRequest $request, Link $link): void
    {
        $rules = $request->input('rules');

        if (! is_array($rules)) {
            return;
        }

        foreach ($rules as $rule) {
            if (! is_array($rule)) {
                continue;
            }

            $enabled = array_key_exists('enabled', $rule) ? (bool) $rule['enabled'] : true;

            $linkRule = $link->rules()->create([
                'priority' => (int) ($rule['priority'] ?? 0),
                'destination_url' => (string) ($rule['destination_url'] ?? ''),
                'is_fallback' => false,
                'enabled' => $enabled,
            ]);

            $conditions = $rule['conditions'] ?? [];

            if (! is_array($conditions)) {
                continue;
            }

            foreach ($conditions as $condition) {
                if (! is_array($condition)) {
                    continue;
                }

                $linkRule->conditions()->create([
                    'condition_type' => (string) ($condition['condition_type'] ?? ''),
                    'operator' => (string) ($condition['operator'] ?? ''),
                    'value' => $condition['value'] ?? null,
                ]);
            }
        }
    }

    private function shortUrl(Request $request, Link $link): string
    {
        $slug = $link->alias ?? $link->code;
        $scheme = parse_url(config('app.url'), PHP_URL_SCHEME) ?: $request->getScheme();

        return sprintf('%s://%s/%s', $scheme, $link->domain->hostname, $slug);
    }
}
