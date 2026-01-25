<?php

use App\Models\BulkImportItem;
use App\Models\BulkImportJob;
use App\Models\Domain;
use App\Models\User;

it('creates a bulk import job via the api', function () {
    $user = User::factory()->create();
    $domain = Domain::factory()->verified()->create([
        'user_id' => $user->id,
    ]);

    $token = $user->createToken('api', [
        config('api.abilities.bulk.read'),
        config('api.abilities.bulk.write'),
    ])->plainTextToken;

    $response = $this->postJson('/api/v1/bulk-imports', [
        'domain_id' => $domain->id,
        'urls' => [
            'https://example.com',
            'https://laravel.com',
        ],
    ], [
        'Authorization' => 'Bearer '.$token,
    ]);

    $response->assertCreated();
    $jobId = $response->json('data.id');

    $this->assertDatabaseHas('bulk_import_jobs', [
        'id' => $jobId,
        'user_id' => $user->id,
        'domain_id' => $domain->id,
    ]);

    expect(BulkImportItem::query()->where('job_id', $jobId)->count())
        ->toBe(2);
});

it('returns bulk import job status via the api', function () {
    $user = User::factory()->create();
    $job = BulkImportJob::factory()->create([
        'user_id' => $user->id,
        'domain_id' => Domain::factory()->verified()->create([
            'user_id' => $user->id,
        ])->id,
    ]);

    $token = $user->createToken('api', [
        config('api.abilities.bulk.read'),
    ])->plainTextToken;

    $response = $this->getJson("/api/v1/bulk-imports/{$job->id}", [
        'Authorization' => 'Bearer '.$token,
    ]);

    $response->assertOk();
    $response->assertJsonPath('data.id', $job->id);
});

it('lists bulk import items via the api', function () {
    $user = User::factory()->create();
    $job = BulkImportJob::factory()->create([
        'user_id' => $user->id,
        'domain_id' => Domain::factory()->verified()->create([
            'user_id' => $user->id,
        ])->id,
    ]);

    $items = BulkImportItem::factory()->count(2)->create([
        'job_id' => $job->id,
    ]);

    $token = $user->createToken('api', [
        config('api.abilities.bulk.read'),
    ])->plainTextToken;

    $response = $this->getJson("/api/v1/bulk-imports/{$job->id}/items", [
        'Authorization' => 'Bearer '.$token,
    ]);

    $response->assertOk();

    expect(collect($response->json('data'))->pluck('id')->all())
        ->toContain($items->first()->id);
});
