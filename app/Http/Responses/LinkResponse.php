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
            'created_at' => optional($link->created_at)->toIso8601String(),
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
}
