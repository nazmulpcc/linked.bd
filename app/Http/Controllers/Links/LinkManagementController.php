<?php

namespace App\Http\Controllers\Links;

use App\Http\Controllers\Controller;
use App\Http\Requests\Links\DestroyLinkRequest;
use App\Models\Link;
use App\Models\LinkVisit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
                    'qr_ready' => $link->qr_path !== null,
                    'qr_download_url' => $link->qr_path
                        ? route('links.qr.download', [
                            'link' => $link->id,
                            'download' => 1,
                        ])
                        : null,
                ];
            });

        return Inertia::render('links/Index', [
            'links' => $links,
        ]);
    }

    public function show(Request $request, Link $link): Response
    {
        if ($link->user_id !== $request->user()->id) {
            abort(404);
        }

        $link->load('domain');

        $visitQuery = LinkVisit::query()->where('link_id', $link->id);

        $visitsByDay = (clone $visitQuery)
            ->selectRaw('DATE(visited_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->map(fn ($row) => [
                'label' => $row->day,
                'value' => (int) $row->total,
            ])
            ->all();

        $topReferrers = (clone $visitQuery)
            ->selectRaw('referrer_host, COUNT(*) as total')
            ->groupBy('referrer_host')
            ->orderByDesc('total')
            ->limit(8)
            ->get()
            ->map(fn ($row) => [
                'label' => $row->referrer_host ?: 'Direct',
                'value' => (int) $row->total,
            ])
            ->all();

        $deviceBreakdown = (clone $visitQuery)
            ->selectRaw('device_type, COUNT(*) as total')
            ->groupBy('device_type')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'label' => $row->device_type ?: 'Unknown',
                'value' => (int) $row->total,
            ])
            ->all();

        $browserBreakdown = (clone $visitQuery)
            ->selectRaw('browser, COUNT(*) as total')
            ->groupBy('browser')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'label' => $row->browser ?: 'Unknown',
                'value' => (int) $row->total,
            ])
            ->all();

        $countryBreakdown = (clone $visitQuery)
            ->selectRaw('country_code, COUNT(*) as total')
            ->groupBy('country_code')
            ->orderByDesc('total')
            ->limit(8)
            ->get()
            ->map(fn ($row) => [
                'label' => $row->country_code ?: 'Unknown',
                'value' => (int) $row->total,
            ])
            ->all();

        return Inertia::render('links/Show', [
            'link' => [
                'id' => $link->id,
                'short_url' => $this->shortUrl($link),
                'destination_url' => $link->destination_url,
                'click_count' => $link->click_count,
                'last_accessed_at' => optional($link->last_accessed_at)->toIso8601String(),
                'expires_at' => optional($link->expires_at)->toIso8601String(),
                'domain' => $link->domain?->hostname,
            ],
            'analytics' => [
                'total_visits' => (clone $visitQuery)->count(),
                'visits_by_day' => $visitsByDay,
                'top_referrers' => $topReferrers,
                'device_breakdown' => $deviceBreakdown,
                'browser_breakdown' => $browserBreakdown,
                'country_breakdown' => $countryBreakdown,
            ],
        ]);
    }

    public function destroy(DestroyLinkRequest $request, Link $link): RedirectResponse
    {
        if ($link->user_id !== $request->user()->id) {
            abort(404);
        }

        $this->deleteQr($link);
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

    private function deleteQr(Link $link): void
    {
        if (! $link->qr_path) {
            return;
        }

        Storage::disk('qr_code')->delete($link->qr_path);
    }
}
