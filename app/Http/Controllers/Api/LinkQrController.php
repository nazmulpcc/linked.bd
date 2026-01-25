<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Link;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class LinkQrController extends Controller
{
    public function show(Request $request, Link $link)
    {
        if ($link->user_id !== $request->user()->id) {
            abort(404);
        }

        if ($request->boolean('download')) {
            return $this->serveQr($link, true);
        }

        $format = $request->string('format')->lower()->toString() ?: 'svg';
        $isPng = $format === 'png';
        $isReady = $isPng || $link->qr_path !== null;

        return response()->json([
            'data' => [
                'status' => $isReady ? 'ready' : 'pending',
                'download_url' => $isReady
                    ? $this->downloadUrl($link, $request)
                    : null,
                'format' => $format,
            ],
            'message' => 'QR status.',
        ]);
    }

    private function downloadUrl(Link $link, Request $request): string
    {
        $query = array_filter([
            'download' => 1,
            'format' => $request->string('format')->lower()->toString() ?: null,
            'w' => $request->integer('w') ?: null,
        ], fn ($value) => $value !== null);

        return route('api.links.qr.show', ['link' => $link->ulid] + $query);
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
        if (! $link->domain) {
            abort(404);
        }

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
