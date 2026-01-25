<?php

namespace App\Http\Middleware;

use App\Models\Domain;
use App\Services\LinkClickRecorder;
use App\Services\LinkRedirectResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectRootDomain
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $appHost = parse_url(config('app.url'), PHP_URL_HOST);

        if (! is_string($appHost) || $appHost === '') {
            return $next($request);
        }

        if ($request->host() === $appHost) {
            return $next($request);
        }

        $domain = Domain::query()
            ->where('hostname', $request->host())
            ->where('status', Domain::STATUS_VERIFIED)
            ->with('redirection')
            ->first();

        if (! $domain) {
            return redirect()->to(config('app.url'));
        }

        $link = $domain->redirection;

        if (! $link) {
            abort(404);
        }

        if ($link->expires_at && $link->expires_at->isPast()) {
            abort(410);
        }

        $resolution = app(LinkRedirectResolver::class)->resolveWithRule($link, $request);
        app(LinkClickRecorder::class)->record($request, $link, $resolution['rule_id'], $resolution['destination_url']);

        return redirect()->away($resolution['destination_url']);
    }
}
