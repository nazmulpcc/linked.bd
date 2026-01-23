<?php

namespace App\Http\Controllers\Links;

use App\Http\Controllers\Controller;
use App\Models\Link;
use App\Models\LinkAccessToken;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class LinkQrController extends Controller
{
    public function download(Request $request, Link $link): Response
    {
        if ($link->user_id !== $request->user()->id) {
            abort(403);
        }

        return $this->serveQr($link, $request->boolean('download'));
    }

    public function downloadGuest(Request $request, string $token): Response
    {
        $accessToken = LinkAccessToken::query()
            ->where('token', $token)
            ->firstOrFail();

        if ($accessToken->expires_at && $accessToken->expires_at->isPast()) {
            abort(410);
        }

        $link = $accessToken->link()->firstOrFail();

        return $this->serveQr($link, $request->boolean('download'));
    }

    private function serveQr(Link $link, bool $download): Response
    {
        if ($this->wantsPng()) {
            return $this->streamPng($link, $download);
        }

        if (! $link->qr_path) {
            abort(404);
        }

        $disk = Storage::disk('qr_code');

        if (! $disk->exists($link->qr_path)) {
            abort(404);
        }

        $filename = sprintf('link-%s-qr.svg', $link->id);

        if ($download) {
            return $disk->download($link->qr_path, $filename, [
                'Content-Type' => 'image/svg+xml',
            ]);
        }

        return $disk->response($link->qr_path, $filename, [
            'Content-Type' => 'image/svg+xml',
            'Content-Disposition' => sprintf('inline; filename=\"%s\"', $filename),
        ]);
    }

    private function streamPng(Link $link, bool $download): Response
    {
        if (! $link->domain) {
            abort(404);
        }

        $qrCode = new QrCode($this->shortUrl($link));
        $qrCode->setSize($this->pngSize());
        $qrCode->setMargin(16);

        $writer = new PngWriter;
        $result = $writer->write($qrCode);
        $filename = sprintf('link-%s-qr.png', $link->id);

        if ($download) {
            return response()->streamDownload(function () use ($result): void {
                echo $result->getString();
            }, $filename, [
                'Content-Type' => 'image/png',
            ]);
        }

        return response($result->getString(), 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => sprintf('inline; filename=\"%s\"', $filename),
        ]);
    }

    private function shortUrl(Link $link): string
    {
        $scheme = parse_url(config('app.url'), PHP_URL_SCHEME) ?: 'https';
        $slug = $link->alias ?? $link->code;

        return sprintf('%s://%s/%s', $scheme, $link->domain->hostname, $slug);
    }

    private function wantsPng(): bool
    {
        return request()->string('format')->lower()->toString() === 'png';
    }

    private function pngSize(): int
    {
        $width = request()->integer('w', 1024);

        if ($width < 128) {
            return 128;
        }

        if ($width > 2048) {
            return 2048;
        }

        return $width;
    }
}
