<?php

namespace App\Services;

use App\Models\BulkImportItem;
use App\Models\BulkImportJob;
use App\Models\Link;
use Illuminate\Support\Str;

class BulkImportPayloadBuilder
{
    /**
     * @return array<string, mixed>
     */
    public function jobPayload(BulkImportJob $job): array
    {
        return [
            'id' => $job->id,
            'status' => $job->status,
            'total_count' => $job->total_count,
            'processed_count' => $job->processed_count,
            'success_count' => $job->success_count,
            'failed_count' => $job->failed_count,
            'created_at' => optional($job->created_at)->toIso8601String(),
            'started_at' => optional($job->started_at)->toIso8601String(),
            'finished_at' => optional($job->finished_at)->toIso8601String(),
        ];
    }

    /**
     * @param  iterable<int, BulkImportItem>  $items
     * @return array<int, array<string, mixed>>
     */
    public function itemsPayload(iterable $items): array
    {
        $payload = [];

        foreach ($items as $item) {
            $payload[] = $this->itemPayload($item);
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    public function itemPayload(BulkImportItem $item): array
    {
        $link = $item->link;
        $shortUrl = $link ? $this->shortUrl($link) : null;

        $qrPreviewUrl = null;
        $qrDownloadUrl = null;
        $qrPngDownloadUrl = null;

        if ($link && $link->qr_path) {
            $qrPreviewUrl = route('links.qr.download', ['link' => $link->ulid]);
            $qrDownloadUrl = route('links.qr.download', [
                'link' => $link->ulid,
                'download' => 1,
            ]);
            $qrPngDownloadUrl = route('links.qr.download', [
                'link' => $link->ulid,
                'download' => 1,
                'format' => 'png',
                'w' => 1024,
            ]);
        }

        return [
            'id' => $item->id,
            'row_number' => $item->row_number,
            'source_url' => $item->source_url,
            'status' => $item->status,
            'error_message' => $item->error_message,
            'link_id' => $item->link_id,
            'short_url' => $shortUrl,
            'qr_status' => $item->qr_status,
            'qr_ready' => $link?->qr_path !== null,
            'qr_preview_url' => $qrPreviewUrl,
            'qr_download_url' => $qrDownloadUrl,
            'qr_png_download_url' => $qrPngDownloadUrl,
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
