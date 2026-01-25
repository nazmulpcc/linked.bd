<?php

namespace App\Http\Responses;

use App\Models\BulkImportItem;
use App\Models\Link;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BulkImportItemResponse extends ApiResponse
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var BulkImportItem $item */
        $item = $this->resource;
        $link = $item->link;

        return [
            'id' => $item->id,
            'row_number' => $item->row_number,
            'source_url' => $item->source_url,
            'status' => $item->status,
            'error_message' => $item->error_message,
            'link_id' => $link?->ulid,
            'short_url' => $link ? $this->shortUrl($link) : null,
            'qr_ready' => $link?->qr_path !== null,
            'qr_download_url' => $link && $link->qr_path
                ? route('api.links.qr.show', [
                    'link' => $link->ulid,
                    'download' => 1,
                ])
                : null,
            'updated_at' => optional($item->updated_at)->toIso8601String(),
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
