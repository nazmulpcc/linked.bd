<?php

namespace App\Http\Controllers\BulkImports;

use App\Http\Controllers\Controller;
use App\Http\Requests\BulkImports\StoreBulkImportRequest;
use App\Models\Domain;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
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

        $jobId = (string) Str::ulid();

        Cache::put("bulk-imports:{$jobId}", [
            'id' => $jobId,
            'domain_id' => $domain->id,
            'total' => count($urls),
            'status' => 'pending',
            'created_at' => now()->toIso8601String(),
            'deduplicate' => $request->boolean('deduplicate'),
            'defaults' => [
                'password' => $request->string('password')->toString(),
                'expires_at' => $request->input('expires_at'),
            ],
        ], now()->addMinutes(30));

        return to_route('bulk-imports.show', ['job' => $jobId])
            ->with('success', 'Bulk import started.');
    }

    public function show(Request $request, string $job): Response
    {
        $payload = Cache::get("bulk-imports:{$job}");

        if (! is_array($payload)) {
            abort(404);
        }

        return Inertia::render('bulk/Show', [
            'job' => $payload,
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
