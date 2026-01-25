<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreBulkImportRequest;
use App\Http\Responses\BulkImportItemResponse;
use App\Http\Responses\BulkImportJobResponse;
use App\Models\BulkImportItem;
use App\Models\BulkImportJob;
use App\Models\Domain;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class BulkImportsController extends Controller
{
    public function store(StoreBulkImportRequest $request): JsonResponse
    {
        $domain = $this->resolveDomainForUser($request);
        $urls = $request->normalizedUrls();

        if ($urls === []) {
            throw ValidationException::withMessages([
                'urls' => 'Provide at least one valid URL.',
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

        return (new BulkImportJobResponse($job))
            ->withMessage('Bulk import started.')
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, string $job): BulkImportJobResponse
    {
        $payload = BulkImportJob::query()
            ->where('id', $job)
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $payload) {
            abort(404);
        }

        return (new BulkImportJobResponse($payload))->withMessage('Bulk import status.');
    }

    public function items(Request $request, string $job): AnonymousResourceCollection
    {
        $payload = BulkImportJob::query()
            ->where('id', $job)
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $payload) {
            abort(404);
        }

        $perPage = $this->clampPerPage($request->integer('per_page', 50));

        $items = BulkImportItem::query()
            ->where('job_id', $payload->id)
            ->with('link.domain')
            ->orderBy('row_number')
            ->paginate($perPage);

        return BulkImportItemResponse::collection($items);
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

    private function clampPerPage(int $perPage): int
    {
        if ($perPage < 1) {
            return 1;
        }

        if ($perPage > 50) {
            return 50;
        }

        return $perPage;
    }
}
