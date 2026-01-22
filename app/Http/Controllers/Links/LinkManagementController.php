<?php

namespace App\Http\Controllers\Links;

use App\Http\Controllers\Controller;
use App\Http\Requests\Links\DestroyLinkRequest;
use App\Models\Link;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class LinkManagementController extends Controller
{
    public function index(Request $request): Response
    {
        $links = Link::query()
            ->where('user_id', $request->user()->id)
            ->with('domain')
            ->latest()
            ->paginate(10)
            ->through(function (Link $link) {
                return [
                    'id' => $link->id,
                    'domain' => $link->domain?->hostname,
                    'short_path' => $link->alias ?? $link->code,
                    'short_url' => $this->shortUrl($link),
                    'destination_url' => $link->destination_url,
                    'click_count' => $link->click_count,
                    'last_accessed_at' => optional($link->last_accessed_at)->toIso8601String(),
                    'expires_at' => optional($link->expires_at)->toIso8601String(),
                    'is_expired' => $link->expires_at !== null && $link->expires_at->isPast(),
                ];
            });

        return Inertia::render('links/Index', [
            'links' => $links,
        ]);
    }

    public function destroy(DestroyLinkRequest $request, Link $link): RedirectResponse
    {
        if ($link->user_id !== $request->user()->id) {
            abort(404);
        }

        $link->delete();

        return to_route('links.index')->with('success', 'Link deleted.');
    }

    private function shortUrl(Link $link): string
    {
        if (! $link->domain) {
            return '';
        }

        $slug = $link->alias ?? $link->code;
        $appScheme = parse_url(config('app.url'), PHP_URL_SCHEME);
        $scheme = $appScheme ?: 'https';

        return sprintf('%s://%s/%s', $scheme, $link->domain->hostname, Str::lower($slug ?? ''));
    }
}
