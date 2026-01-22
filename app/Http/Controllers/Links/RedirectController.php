<?php

namespace App\Http\Controllers\Links;

use App\Http\Controllers\Controller;
use App\Http\Requests\Links\UnlockLinkRequest;
use App\Models\Domain;
use App\Models\Link;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

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
            return redirect()->away($link->destination_url);
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
            return Inertia::location($link->destination_url);
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

        return Inertia::location($link->destination_url);
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
}
