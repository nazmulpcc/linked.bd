<?php

namespace App\Jobs;

use App\Events\LinkQrGenerated;
use App\Models\BulkImportItem;
use App\Models\Link;
use App\Models\LinkAccessToken;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

class GenerateQrForLink implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $linkId) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $link = Link::query()
            ->with('domain')
            ->find($this->linkId);

        if (! $link) {
            return;
        }

        if ($link->qr_path) {
            return;
        }

        $shortUrl = $this->shortUrl($link);
        $qrCode = new QrCode($shortUrl);
        $qrCode->setSize(320);
        $qrCode->setMargin(16);

        $writer = new SvgWriter;
        $result = $writer->write($qrCode);

        $path = sprintf('links/%s.svg', $link->id);

        Storage::disk('qr_code')->put($path, $result->getString());

        $link->forceFill([
            'qr_path' => $path,
        ])->save();

        if ($link->wasChanged('qr_path') === false) {
            return;
        }

        BulkImportItem::query()
            ->where('link_id', $link->id)
            ->update([
                'qr_status' => 'ready',
                'updated_at' => now(),
            ]);

        $token = LinkAccessToken::query()
            ->where('link_id', $link->id)
            ->latest()
            ->value('token');

        if (! $token) {
            return;
        }

        event(new LinkQrGenerated($token, [
            'previewUrl' => route('links.qr.guest', ['token' => $token]),
            'downloadUrl' => route('links.qr.guest', ['token' => $token, 'download' => 1]),
            'pngDownloadUrl' => route('links.qr.guest', [
                'token' => $token,
                'download' => 1,
                'format' => 'png',
                'w' => 1024,
            ]),
        ]));
    }

    private function shortUrl(Link $link): string
    {
        $scheme = parse_url(config('app.url'), PHP_URL_SCHEME) ?: 'https';
        $slug = $link->alias ?? $link->code;

        return sprintf('%s://%s/%s', $scheme, $link->domain->hostname, $slug);
    }
}
