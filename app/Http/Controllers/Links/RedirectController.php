<?php

namespace App\Http\Controllers\Links;

use App\Http\Controllers\Controller;
use App\Models\Domain;
use App\Models\Link;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RedirectController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, string $slug): RedirectResponse
    {
        $domain = Domain::query()
            ->where('hostname', $request->host())
            ->where('status', Domain::STATUS_VERIFIED)
            ->first();

        if (! $domain) {
            abort(404);
        }

        $normalizedSlug = Str::lower($slug);

        $linkQuery = Link::query()
            ->where('domain_id', $domain->id);

        if ($domain->type === Domain::TYPE_CUSTOM) {
            $linkQuery->where('alias', $normalizedSlug);
        } else {
            $linkQuery->where('code', $normalizedSlug);
        }

        $link = $linkQuery->first();

        if (! $link) {
            abort(404);
        }

        if ($link->expires_at && $link->expires_at->isPast()) {
            abort(410);
        }

        return redirect()->away($link->destination_url);
    }
}
