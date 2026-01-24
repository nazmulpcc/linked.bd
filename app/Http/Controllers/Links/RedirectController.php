<?php

namespace App\Http\Controllers\Links;

use App\Http\Controllers\Controller;
use App\Http\Requests\Links\UnlockLinkRequest;
use App\Jobs\RecordLinkClick;
use App\Models\Domain;
use App\Models\Link;
use App\Services\IpCountryResolver;
use App\Services\LinkRedirectResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class RedirectController extends Controller
{
    public function show(Request $request, string $slug): Response|RedirectResponse
    {
        $domain = $this->resolveDomain($request);

        if (! $domain) {
            abort(404);
        }

        $link = $this->resolveLink($domain, $slug);

        if (! $link) {
            abort(404);
        }

        if ($link->expires_at && $link->expires_at->isPast()) {
            abort(410);
        }

        if (! $link->password_hash) {
            $destinationUrl = $this->resolveDestination($request, $link);
            $this->recordClick($request, $link);

            return redirect()->away($destinationUrl);
        }

        return Inertia::render('links/Password', [
            'slug' => $slug,
            'shortUrl' => sprintf(
                '%s://%s/%s',
                $request->getScheme(),
                $request->host(),
                $slug,
            ),
        ]);
    }

    public function unlock(UnlockLinkRequest $request, string $slug)
    {
        $domain = $this->resolveDomain($request);

        if (! $domain) {
            abort(404);
        }

        $link = $this->resolveLink($domain, $slug);

        if (! $link) {
            abort(404);
        }

        if ($link->expires_at && $link->expires_at->isPast()) {
            abort(410);
        }

        if (! $link->password_hash) {
            $destinationUrl = $this->resolveDestination($request, $link);
            $this->recordClick($request, $link);

            return Inertia::location($destinationUrl);
        }

        $key = $this->throttleKey($request, $slug);

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            throw ValidationException::withMessages([
                'password' => "Too many attempts. Try again in {$seconds} seconds.",
            ])->status(429);
        }

        $password = $request->string('password')->toString();

        if (! Hash::check($password, $link->password_hash)) {
            RateLimiter::hit($key, 60);

            throw ValidationException::withMessages([
                'password' => 'That password is incorrect.',
            ]);
        }

        RateLimiter::clear($key);

        $destinationUrl = $this->resolveDestination($request, $link);
        $this->recordClick($request, $link);

        return Inertia::location($destinationUrl);
    }

    private function resolveDomain(Request $request): ?Domain
    {
        return Domain::query()
            ->where('hostname', $request->host())
            ->where('status', Domain::STATUS_VERIFIED)
            ->first();
    }

    private function resolveLink(Domain $domain, string $slug): ?Link
    {
        $normalizedSlug = Str::lower($slug);

        $linkQuery = Link::query()
            ->where('domain_id', $domain->id);

        if ($domain->type === Domain::TYPE_CUSTOM) {
            $linkQuery->where('alias', $normalizedSlug);
        } else {
            $linkQuery->where('code', $normalizedSlug);
        }

        return $linkQuery->first();
    }

    private function throttleKey(Request $request, string $slug): string
    {
        return sprintf(
            'link-password:%s:%s:%s',
            $request->ip(),
            $request->host(),
            Str::lower($slug),
        );
    }

    private function recordClick(Request $request, Link $link): void
    {
        $visitData = $this->visitData($request);

        try {
            RecordLinkClick::dispatch($link->id, $visitData);
        } catch (Throwable $exception) {
            $this->recordClickSync($link, $visitData);
        }
    }

    private function recordClickSync(Link $link, array $visitData): void
    {
        if ($link->expires_at && $link->expires_at->isPast()) {
            return;
        }

        $link->increment('click_count');
        $link->forceFill([
            'last_accessed_at' => now(),
        ])->save();

        $link->visits()->create($visitData);
    }

    /**
     * @return array{visited_at: string, referrer_host: string|null, device_type: string|null, browser: string|null, country_code: string|null, user_agent: string|null}
     */
    private function visitData(Request $request): array
    {
        $userAgent = $request->userAgent();
        $referrer = $request->headers->get('referer');
        $referrerHost = null;

        if (is_string($referrer) && $referrer !== '') {
            $referrerHost = parse_url($referrer, PHP_URL_HOST);

            if (! is_string($referrerHost) || $referrerHost === '') {
                $referrerHost = null;
            }
        }

        return [
            'visited_at' => now()->toDateTimeString(),
            'referrer_host' => $referrerHost,
            'device_type' => $this->deviceType($userAgent),
            'browser' => $this->browserName($userAgent),
            'country_code' => $this->countryCode($request),
            'user_agent' => $userAgent,
        ];
    }

    private function deviceType(?string $userAgent): ?string
    {
        if (! is_string($userAgent) || $userAgent === '') {
            return null;
        }

        $agent = Str::lower($userAgent);

        if (Str::contains($agent, ['mobile', 'iphone', 'android', 'ipad'])) {
            return 'mobile';
        }

        return 'desktop';
    }

    private function browserName(?string $userAgent): ?string
    {
        if (! is_string($userAgent) || $userAgent === '') {
            return null;
        }

        $agent = Str::lower($userAgent);

        if (Str::contains($agent, 'edg/')) {
            return 'edge';
        }

        if (Str::contains($agent, 'chrome/') && ! Str::contains($agent, 'edg/')) {
            return 'chrome';
        }

        if (Str::contains($agent, 'firefox/')) {
            return 'firefox';
        }

        if (Str::contains($agent, 'safari/') && ! Str::contains($agent, 'chrome/')) {
            return 'safari';
        }

        return 'other';
    }

    private function countryCode(Request $request): ?string
    {
        $ip = $request->ip();

        if (! is_string($ip) || $ip === '') {
            return null;
        }

        return app(IpCountryResolver::class)->resolve($ip);
    }

    private function resolveDestination(Request $request, Link $link): string
    {
        return app(LinkRedirectResolver::class)->resolve($link, $request);
    }
}
