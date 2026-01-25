<?php

namespace App\Http\Controllers\BulkImports;

use App\Http\Controllers\Controller;
use App\Http\Requests\BulkImports\StoreBulkImportRequest;
use App\Models\BulkImportItem;
use App\Models\BulkImportJob;
use App\Models\Domain;
use App\Services\BulkImportPayloadBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class BulkImportController extends Controller
{
    public function __construct(private BulkImportPayloadBuilder $payloadBuilder) {}

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

        \App\Jobs\BulkImports\ProcessBulkImportJob::dispatch($job->id);

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
            'job' => $this->payloadBuilder->jobPayload($payload),
            'items' => $this->payloadBuilder->itemsPayload($items),
            'lastUpdatedAt' => $lastUpdatedAt
                ? $lastUpdatedAt->toIso8601String()
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
            'job' => $this->payloadBuilder->jobPayload($payload),
            'items' => $this->payloadBuilder->itemsPayload($items),
            'last_updated_at' => $lastUpdatedAt
                ? $lastUpdatedAt->toIso8601String()
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
