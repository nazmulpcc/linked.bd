<?php

namespace App\Http\Controllers\BulkImports;

use App\Http\Controllers\Controller;
use App\Http\Requests\BulkImports\StoreBulkImportRequest;
use App\Models\BulkImportItem;
use App\Models\BulkImportJob;
use App\Models\Domain;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

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

        $job = BulkImportJob::query()->create([
            'user_id' => $request->user()->id,
            'domain_id' => $domain->id,
            'status' => BulkImportJob::STATUS_PENDING,
            'total_count' => count($urls),
            'processed_count' => 0,
            'success_count' => 0,
            'failed_count' => 0,
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

        return Inertia::render('bulk/Show', [
            'job' => [
                'id' => $payload->id,
                'status' => $payload->status,
                'total_count' => $payload->total_count,
                'processed_count' => $payload->processed_count,
                'success_count' => $payload->success_count,
                'failed_count' => $payload->failed_count,
                'created_at' => optional($payload->created_at)->toIso8601String(),
                'started_at' => optional($payload->started_at)->toIso8601String(),
                'finished_at' => optional($payload->finished_at)->toIso8601String(),
            ],
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
}
