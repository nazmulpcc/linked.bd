<?php

use App\Models\BulkImportJob;
use App\Models\Domain;
use App\Models\User;

it('renders the bulk import history page', function () {
    $user = User::factory()->create();
    $domain = Domain::factory()->for($user)->verified()->create();

    BulkImportJob::factory()
        ->for($user)
        ->for($domain)
        ->create([
            'status' => BulkImportJob::STATUS_COMPLETED,
            'total_count' => 2,
            'processed_count' => 2,
            'success_count' => 2,
            'failed_count' => 0,
        ]);

    $response = $this->actingAs($user)->get('/dashboard/bulk-imports/history');

    $response->assertOk();
});
