<?php

namespace App\Jobs\BulkImports;

use App\Models\BulkImportItem;
use App\Models\BulkImportJob;
use Illuminate\Bus\Batch;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Date;
use Throwable;

class ProcessBulkImportJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public BulkImportJob $job) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $job = $this->job->fresh();

        if (! $job) {
            return;
        }

        if (in_array($job->status, [
            BulkImportJob::STATUS_COMPLETED,
            BulkImportJob::STATUS_COMPLETED_WITH_ERRORS,
            BulkImportJob::STATUS_FAILED,
            BulkImportJob::STATUS_CANCELLED,
        ], true)) {
            return;
        }

        if ($job->status === BulkImportJob::STATUS_RUNNING) {
            return;
        }

        $job->forceFill([
            'status' => BulkImportJob::STATUS_RUNNING,
            'started_at' => $job->started_at ?? Date::now(),
        ])->save();

        $itemIds = BulkImportItem::query()
            ->where('job_id', $job->id)
            ->whereIn('status', [
                BulkImportItem::STATUS_QUEUED,
                BulkImportItem::STATUS_PROCESSING,
            ])
            ->pluck('id');

        if ($itemIds->isEmpty()) {
            self::finalizeJobById($job->id);

            return;
        }

        $jobs = $itemIds->chunk(50)->map(function ($chunk) use ($job) {
            return new ProcessBulkImportChunk($job->id, $chunk->all());
        })->all();

        $jobId = $job->id;

        Bus::batch($jobs)
            ->name("bulk-import-{$job->id}")
            ->then(static function (Batch $batch) use ($jobId): void {
                self::finalizeJobById($jobId);
            })
            ->catch(static function (Batch $batch, Throwable $exception) use ($jobId): void {
                BulkImportJob::query()
                    ->whereKey($jobId)
                    ->update([
                        'status' => BulkImportJob::STATUS_FAILED,
                        'finished_at' => Date::now(),
                    ]);
            })
            ->dispatch();
    }

    private static function finalizeJobById(int $jobId): void
    {
        $job = BulkImportJob::query()->find($jobId);

        if (! $job) {
            return;
        }

        if (in_array($job->status, [
            BulkImportJob::STATUS_FAILED,
            BulkImportJob::STATUS_CANCELLED,
        ], true)) {
            if (! $job->finished_at) {
                $job->forceFill(['finished_at' => Date::now()])->save();
            }

            return;
        }

        $job->forceFill([
            'status' => $job->failed_count > 0
                ? BulkImportJob::STATUS_COMPLETED_WITH_ERRORS
                : BulkImportJob::STATUS_COMPLETED,
            'finished_at' => Date::now(),
        ])->save();
    }
}
