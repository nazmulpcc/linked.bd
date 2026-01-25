<?php

namespace App\Http\Controllers\Api;

use App\Enums\LinkType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreLinkRequest;
use App\Http\Responses\LinkResponse;
use App\Models\Domain;
use App\Models\Link;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LinksController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $this->clampPerPage($request->integer('per_page', 10));

        $links = Link::query()
            ->where('user_id', $request->user()->id)
            ->with('domain')
            ->latest()
            ->paginate($perPage);

        return LinkResponse::collection($links);
    }

    public function store(StoreLinkRequest $request): JsonResponse
    {
        $domain = $this->resolveDomainForCreate($request);
        $alias = $this->normalizeAlias($request->string('alias')->toString());

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
        $linkType = LinkType::tryFrom($request->string('link_type')->toString()) ?? LinkType::Static;

        if ($linkType === LinkType::Dynamic) {
            $destinationUrl = $fallbackDestination;
        }

        $link = Link::query()->create([
            'domain_id' => $domain->id,
            'user_id' => $request->user()->id,
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

        event(new \App\Events\LinkCreated($link));

        return (new LinkResponse($link->load('domain')))
            ->withMessage('Link created.')
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, Link $link): LinkResponse
    {
        $this->authorizeLink($request, $link);

        $link->load(['domain', 'rules.conditions']);

        return (new LinkResponse($link))->withMessage('Link details.');
    }

    public function destroy(Request $request, Link $link): JsonResponse
    {
        $this->authorizeLink($request, $link);
        $this->deleteQr($link);
        $link->delete();

        return response()->json([
            'message' => 'Link deleted.',
        ]);
    }

    private function authorizeLink(Request $request, Link $link): void
    {
        if ($link->user_id !== $request->user()->id) {
            abort(404);
        }
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

        if ($domain->type === Domain::TYPE_CUSTOM && $domain->user_id !== $request->user()->id) {
            throw ValidationException::withMessages([
                'domain_id' => 'Choose one of your verified domains.',
            ]);
        }

        return $domain;
    }

    private function resolveExpiry(StoreLinkRequest $request): ?\Carbon\CarbonInterface
    {
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

    private function deleteQr(Link $link): void
    {
        if (! $link->qr_path) {
            return;
        }

        Storage::disk('qr_code')->delete($link->qr_path);
    }

    private function clampPerPage(int $perPage): int
    {
        if ($perPage < 1) {
            return 1;
        }

        if ($perPage > 50) {
            return 50;
        }

        return $perPage;
    }
}
