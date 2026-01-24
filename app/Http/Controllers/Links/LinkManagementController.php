<?php

namespace App\Http\Controllers\Links;

use App\Enums\LinkType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Links\DestroyLinkRequest;
use App\Http\Requests\Links\UpdateDynamicLinkRequest;
use App\Models\Link;
use App\Models\LinkRule;
use App\Models\LinkVisit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
                    'ulid' => $link->ulid,
                    'domain' => $link->domain?->hostname,
                    'short_path' => $link->alias ?? $link->code,
                    'short_url' => $this->shortUrl($link),
                    'destination_url' => $link->destination_url,
                    'link_type' => $link->link_type->value,
                    'click_count' => $link->click_count,
                    'last_accessed_at' => optional($link->last_accessed_at)->toIso8601String(),
                    'expires_at' => optional($link->expires_at)->toIso8601String(),
                    'is_expired' => $link->expires_at !== null && $link->expires_at->isPast(),
                    'qr_ready' => $link->qr_path !== null,
                    'qr_download_url' => $link->qr_path
                        ? route('links.qr.download', [
                            'link' => $link->ulid,
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

        $link->load([
            'domain',
            'rules.conditions',
        ]);

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
                'ulid' => $link->ulid,
                'short_url' => $this->shortUrl($link),
                'destination_url' => $link->destination_url,
                'link_type' => $link->link_type->value,
                'fallback_destination_url' => $link->fallback_destination_url,
                'click_count' => $link->click_count,
                'last_accessed_at' => optional($link->last_accessed_at)->toIso8601String(),
                'expires_at' => optional($link->expires_at)->toIso8601String(),
                'domain' => $link->domain?->hostname,
            ],
            'dynamic' => $link->link_type === LinkType::Dynamic
                ? [
                    'rules' => $link->rules
                        ->sortBy('priority')
                        ->values()
                        ->map(fn (LinkRule $rule) => [
                            'id' => $rule->id,
                            'priority' => $rule->priority,
                            'destination_url' => $rule->destination_url,
                            'enabled' => $rule->enabled,
                            'conditions' => $rule->conditions->map(fn ($condition) => [
                                'id' => $condition->id,
                                'condition_type' => $condition->condition_type?->value ?? null,
                                'operator' => $condition->operator?->value ?? null,
                                'value' => $condition->value,
                            ])->all(),
                        ])
                        ->all(),
                ]
                : null,
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

    public function updateDynamic(UpdateDynamicLinkRequest $request, Link $link): RedirectResponse
    {
        if ($link->user_id !== $request->user()->id || $link->link_type !== LinkType::Dynamic) {
            abort(404);
        }

        DB::transaction(function () use ($request, $link) {
            $fallback = $request->string('fallback_destination_url')->toString();

            $link->forceFill([
                'fallback_destination_url' => $fallback,
                'destination_url' => $fallback,
            ])->save();

            $link->rules()->delete();

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
        });

        return back()->with('success', 'Dynamic rules updated.');
    }

    public function clone(Request $request, Link $link): RedirectResponse
    {
        if ($link->user_id !== $request->user()->id) {
            abort(404);
        }

        $link->load(['domain', 'rules.conditions']);

        $clone = DB::transaction(function () use ($link) {
            $domain = $link->domain;

            if (! $domain) {
                abort(404);
            }

            $newLink = Link::query()->create([
                'domain_id' => $domain->id,
                'user_id' => $link->user_id,
                'code' => $this->generateCode($domain->id),
                'alias' => null,
                'link_type' => $link->link_type,
                'destination_url' => $link->destination_url,
                'fallback_destination_url' => $link->fallback_destination_url,
                'password_hash' => $link->password_hash,
                'expires_at' => $link->expires_at,
                'click_count' => 0,
                'last_accessed_at' => null,
                'qr_path' => null,
            ]);

            if ($link->link_type === LinkType::Dynamic) {
                $rules = $link->rules->sortBy('priority');

                foreach ($rules as $rule) {
                    $newRule = $newLink->rules()->create([
                        'priority' => $rule->priority,
                        'destination_url' => $rule->destination_url,
                        'is_fallback' => $rule->is_fallback,
                        'enabled' => $rule->enabled,
                    ]);

                    foreach ($rule->conditions as $condition) {
                        $newRule->conditions()->create([
                            'condition_type' => $condition->condition_type?->value ?? $condition->condition_type,
                            'operator' => $condition->operator?->value ?? $condition->operator,
                            'value' => $condition->value,
                        ]);
                    }
                }
            }

            return $newLink;
        });

        return to_route('links.show', ['link' => $clone->ulid])
            ->with('success', 'Link cloned.');
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

    private function generateCode(int $domainId): string
    {
        $attempts = 0;

        while ($attempts < 8) {
            $attempts++;
            $code = Str::lower(Str::random(7));

            $exists = Link::query()
                ->where('domain_id', $domainId)
                ->where('code', $code)
                ->exists();

            if (! $exists) {
                return $code;
            }
        }

        abort(422, 'Unable to generate a unique short code.');
    }
}
