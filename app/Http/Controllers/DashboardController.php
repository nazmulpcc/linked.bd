<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Models\Link;
use App\Models\LinkVisit;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        $clicksToday = LinkVisit::query()
            ->whereDate('visited_at', now()->toDateString())
            ->whereHas('link', fn ($query) => $query->where('user_id', $user->id))
            ->count();

        $activeLinks = Link::query()
            ->where('user_id', $user->id)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->count();

        $customDomains = Domain::query()
            ->where('user_id', $user->id)
            ->where('type', Domain::TYPE_CUSTOM)
            ->count();

        $protectedLinks = Link::query()
            ->where('user_id', $user->id)
            ->whereNotNull('password_hash')
            ->count();

        $recentLinks = Link::query()
            ->where('user_id', $user->id)
            ->with('domain')
            ->orderByDesc('last_accessed_at')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(fn (Link $link) => [
                'id' => $link->id,
                'ulid' => $link->ulid,
                'short_url' => $this->shortUrl($link),
                'destination_url' => $link->destination_url,
                'click_count' => $link->click_count,
                'last_accessed_at' => optional($link->last_accessed_at)->toIso8601String(),
            ])
            ->all();

        return Inertia::render('Dashboard', [
            'stats' => [
                'clicks_today' => $clicksToday,
                'active_links' => $activeLinks,
                'custom_domains' => $customDomains,
                'protected_links' => $protectedLinks,
            ],
            'recent_links' => $recentLinks,
        ]);
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
