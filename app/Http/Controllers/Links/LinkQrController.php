<?php

namespace App\Http\Controllers\Links;

use App\Http\Controllers\Controller;
use App\Models\Link;
use App\Models\LinkAccessToken;
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
}
