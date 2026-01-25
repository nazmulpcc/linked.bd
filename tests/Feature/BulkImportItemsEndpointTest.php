<?php

use App\Models\BulkImportItem;
use App\Models\BulkImportJob;
use App\Models\Domain;
use App\Models\Link;
use App\Models\User;

it('returns bulk import item updates', function () {
    $user = User::factory()->create();
    $domain = Domain::factory()->for($user)->verified()->create();

    $job = BulkImportJob::factory()
        ->for($user)
        ->for($domain)
        ->create();

    $link = Link::factory()->for($domain)->for($user)->create([
        'qr_path' => 'links/example.svg',
    ]);

    $item = BulkImportItem::factory()
        ->for($job, 'job')
        ->create([
            'status' => BulkImportItem::STATUS_SUCCEEDED,
            'link_id' => $link->id,
            'qr_status' => 'ready',
        ]);

    $response = $this->actingAs($user)->getJson("/dashboard/bulk-imports/{$job->id}/items");

    $response->assertOk();
    $response->assertJsonPath('job.id', $job->id);
    $response->assertJsonPath('items.0.id', $item->id);
    $response->assertJsonPath('items.0.short_url', function ($value) use ($domain) {
        return is_string($value) && str_contains($value, $domain->hostname);
    });
});
