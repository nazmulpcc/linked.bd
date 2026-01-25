<?php

namespace App\Http\Controllers\BulkImports;

use App\Http\Controllers\Controller;
use App\Http\Requests\BulkImports\StoreBulkImportRequest;
use App\Models\BulkImportItem;
use App\Models\BulkImportJob;
use App\Models\Domain;
use App\Models\Link;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class BulkImportController extends Controller
{
    public function index(Request $request): Response
    {
        $domains = Domain::query()
            ->where('status', Domain::STATUS_VERIFIED)
            ->where(function ($query) use ($request) {
                $query->where('type', Domain::TYPE_PLATFORM);

                $query->orWhere(function ($nested) use ($request) {
                    $nested->where('type', Domain::TYPE_CUSTOM)
                        ->where('user_id', $request->user()->id);
                });
            })
            ->orderBy('type')
            ->orderBy('hostname')
            ->get(['id', 'hostname', 'type']);

        return Inertia::render('bulk/Index', [
            'domains' => $domains,
        ]);
    }

    public function store(StoreBulkImportRequest $request): RedirectResponse
    {
        $domain = $this->resolveDomainForUser($request);
        $urls = $request->normalizedUrls();

        if ($urls === []) {
            throw ValidationException::withMessages([
                'urls' => 'Paste at least one valid URL.',
            ]);
        }

        $password = $request->string('password')->toString();

        $job = BulkImportJob::query()->create([
            'user_id' => $request->user()->id,
            'domain_id' => $domain->id,
            'status' => BulkImportJob::STATUS_PENDING,
            'total_count' => count($urls),
            'processed_count' => 0,
            'success_count' => 0,
            'failed_count' => 0,
            'default_password_hash' => $password === '' ? null : Hash::make($password),
            'default_expires_at' => $request->input('expires_at'),
            'started_at' => null,
            'finished_at' => null,
        ]);

        $items = [];

        foreach ($urls as $index => $url) {
            $items[] = [
                'job_id' => $job->id,
                'row_number' => $index + 1,
                'source_url' => $url,
                'status' => BulkImportItem::STATUS_QUEUED,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        BulkImportItem::query()->insert($items);

        \App\Jobs\BulkImports\ProcessBulkImportJob::dispatch($job);

        return to_route('bulk-imports.show', ['job' => $job->id])
            ->with('success', 'Bulk import started.');
    }

    public function show(Request $request, string $job): Response
    {
        $payload = BulkImportJob::query()
            ->where('id', $job)
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $payload) {
            abort(404);
        }

        $items = BulkImportItem::query()
            ->where('job_id', $payload->id)
            ->with('link.domain')
            ->orderBy('row_number')
            ->get();

        $lastUpdatedAt = $items->max('updated_at');

        return Inertia::render('bulk/Show', [
            'job' => $this->jobPayload($payload),
            'items' => $this->itemsPayload($items),
            'lastUpdatedAt' => $lastUpdatedAt
                ? Date::parse($lastUpdatedAt)->toIso8601String()
                : null,
        ]);
    }

    public function items(Request $request, string $job): JsonResponse
    {
        $payload = BulkImportJob::query()
            ->where('id', $job)
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $payload) {
            abort(404);
        }

        $since = $this->parseSince($request->input('since'));

        $itemsQuery = BulkImportItem::query()
            ->where('job_id', $payload->id)
            ->with('link.domain')
            ->orderBy('updated_at')
            ->limit(200);

        if ($since) {
            $itemsQuery->where('updated_at', '>', $since);
        }

        $items = $itemsQuery->get();
        $lastUpdatedAt = $items->max('updated_at')
            ?? BulkImportItem::query()->where('job_id', $payload->id)->max('updated_at');

        return response()->json([
            'job' => $this->jobPayload($payload),
            'items' => $this->itemsPayload($items),
            'last_updated_at' => $lastUpdatedAt
                ? Date::parse($lastUpdatedAt)->toIso8601String()
                : null,
        ]);
    }

    private function resolveDomainForUser(StoreBulkImportRequest $request): Domain
    {
        $domain = Domain::query()->find($request->integer('domain_id'));

        if (! $domain) {
            throw ValidationException::withMessages([
                'domain_id' => 'Choose a valid domain.',
            ]);
        }

        if ($domain->status !== Domain::STATUS_VERIFIED) {
            throw ValidationException::withMessages([
                'domain_id' => 'This domain is not verified yet.',
            ]);
        }

        if ($domain->type === Domain::TYPE_CUSTOM && $domain->user_id !== $request->user()->id) {
            throw ValidationException::withMessages([
                'domain_id' => 'Choose one of your verified domains.',
            ]);
        }

        return $domain;
    }

    /**
     * @return array<string, mixed>
     */
    private function jobPayload(BulkImportJob $job): array
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
    private function itemsPayload(iterable $items): array
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
    private function itemPayload(BulkImportItem $item): array
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

    private function parseSince(mixed $since): ?\Carbon\CarbonInterface
    {
        if (! is_string($since) || $since === '') {
            return null;
        }

        try {
            return Date::parse($since);
        } catch (Throwable $exception) {
            return null;
        }
    }
}
