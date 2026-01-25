<?php

namespace App\Http\Responses;

use App\Enums\LinkType;
use App\Models\Link;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LinkResponse extends ApiResponse
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Link $link */
        $link = $this->resource;

        return [
            'id' => $link->ulid,
            'domain' => $link->domain?->hostname,
            'short_path' => $link->alias ?? $link->code,
            'short_url' => $this->shortUrl($link),
            'destination_url' => $link->destination_url,
            'fallback_destination_url' => $link->fallback_destination_url,
            'link_type' => $link->link_type instanceof LinkType ? $link->link_type->value : $link->link_type,
            'expires_at' => optional($link->expires_at)->toIso8601String(),
            'click_count' => $link->click_count,
            'last_accessed_at' => optional($link->last_accessed_at)->toIso8601String(),
            'qr_ready' => $link->qr_path !== null,
            'qr_download_url' => $link->qr_path
                ? route('api.links.qr.show', [
                    'link' => $link->ulid,
                    'download' => 1,
                ])
                : null,
            'created_at' => optional($link->created_at)->toIso8601String(),
            'rules' => $this->rulesPayload($link),
        ];
    }

    private function shortUrl(Link $link): ?string
    {
        $hostname = $link->domain?->hostname;
        $slug = $link->alias ?? $link->code;

        if (! $hostname || ! $slug) {
            return null;
        }

        $appScheme = parse_url(config('app.url'), PHP_URL_SCHEME);
        $scheme = $appScheme ?: 'https';

        return sprintf('%s://%s/%s', $scheme, $hostname, Str::lower($slug));
    }

    /**
     * @return array<int, array<string, mixed>>|null
     */
    private function rulesPayload(Link $link): ?array
    {
        if (! $link->relationLoaded('rules')) {
            return null;
        }

        return $link->rules->map(function ($rule): array {
            return [
                'id' => $rule->id,
                'priority' => $rule->priority,
                'destination_url' => $rule->destination_url,
                'is_fallback' => (bool) $rule->is_fallback,
                'enabled' => (bool) $rule->enabled,
                'conditions' => $rule->relationLoaded('conditions')
                    ? $rule->conditions->map(function ($condition): array {
                        return [
                            'id' => $condition->id,
                            'condition_type' => $condition->condition_type,
                            'operator' => $condition->operator,
                            'value' => $condition->value,
                        ];
                    })->all()
                    : null,
            ];
        })->all();
    }
}
