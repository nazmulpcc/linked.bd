<?php

use App\Events\LinkCreated;
use App\Jobs\BulkImports\ProcessBulkImportChunk;
use App\Models\BulkImportItem;
use App\Models\BulkImportJob;
use App\Models\Domain;
use App\Models\Link;
use App\Models\User;
use Illuminate\Support\Facades\Event;

it('processes bulk items and creates links', function () {
    Event::fake([LinkCreated::class]);

    $user = User::factory()->create();
    $domain = Domain::factory()->for($user)->verified()->create();

    $job = BulkImportJob::factory()
        ->for($user)
        ->for($domain)
        ->create([
            'status' => BulkImportJob::STATUS_RUNNING,
            'total_count' => 2,
        ]);

    $items = BulkImportItem::factory()
        ->count(2)
        ->for($job, 'job')
        ->create([
            'status' => BulkImportItem::STATUS_QUEUED,
        ]);

    $processor = new ProcessBulkImportChunk($job->id, $items->pluck('id')->all());
    $processor->handle();

    $job->refresh();

    expect($job->processed_count)->toBe(2)
        ->and($job->success_count)->toBe(2)
        ->and($job->failed_count)->toBe(0);

    expect(BulkImportItem::query()->where('job_id', $job->id)->where('status', BulkImportItem::STATUS_SUCCEEDED)->count())
        ->toBe(2);
    expect(Link::query()->count())->toBe(2);

    Event::assertDispatched(LinkCreated::class, 2);
});

it('marks invalid urls as failed', function () {
    $user = User::factory()->create();
    $domain = Domain::factory()->for($user)->verified()->create();

    $job = BulkImportJob::factory()
        ->for($user)
        ->for($domain)
        ->create([
            'status' => BulkImportJob::STATUS_RUNNING,
            'total_count' => 1,
        ]);

    $item = BulkImportItem::factory()
        ->for($job, 'job')
        ->create([
            'status' => BulkImportItem::STATUS_QUEUED,
            'source_url' => 'http://127.0.0.1',
        ]);

    $processor = new ProcessBulkImportChunk($job->id, [$item->id]);
    $processor->handle();

    $item->refresh();
    $job->refresh();

    expect($item->status)->toBe(BulkImportItem::STATUS_FAILED)
        ->and($item->error_message)->toContain('private');
    expect($job->processed_count)->toBe(1)
        ->and($job->failed_count)->toBe(1)
        ->and($job->success_count)->toBe(0);
    expect(Link::query()->count())->toBe(0);
});
