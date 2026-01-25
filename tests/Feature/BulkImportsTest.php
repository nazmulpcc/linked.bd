<?php

use App\Models\BulkImportItem;
use App\Models\BulkImportJob;
use App\Models\Domain;
use App\Models\User;

it('renders the bulk import page', function () {
    $user = User::factory()->create();
    Domain::factory()->for($user)->verified()->create();

    $response = $this->actingAs($user)->get('/dashboard/bulk-imports');

    $response->assertOk();
});

it('stores a bulk import request and redirects to the job page', function () {
    $user = User::factory()->create();
    $domain = Domain::factory()->for($user)->verified()->create();

    $response = $this->actingAs($user)->post('/dashboard/bulk-imports', [
        'domain_id' => $domain->id,
        'urls' => "https://example.com\nhttps://example.com/second",
        'deduplicate' => true,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $location = $response->headers->get('Location');
    expect($location)->toContain('/dashboard/bulk-imports/');

    $path = parse_url($location, PHP_URL_PATH);
    $jobId = $path ? basename($path) : null;

    expect($jobId)->not->toBeNull();

    $job = BulkImportJob::query()->where('id', $jobId)->first();
    expect($job)->not->toBeNull();
    expect(BulkImportItem::query()->where('job_id', $job->id)->count())->toBe(2);
});
