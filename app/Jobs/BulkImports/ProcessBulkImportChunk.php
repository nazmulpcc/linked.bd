<?php

namespace App\Jobs\BulkImports;

use App\Enums\LinkType;
use App\Events\BulkImportJobUpdated;
use App\Events\LinkCreated;
use App\Models\BulkImportItem;
use App\Models\BulkImportJob;
use App\Models\Domain;
use App\Models\Link;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class ProcessBulkImportChunk implements ShouldQueue
{
    use Batchable;
    use Queueable;

    /**
     * @param  array<int, int>  $itemIds
     */
    public function __construct(public string $jobId, public array $itemIds) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $job = BulkImportJob::query()
            ->with('domain')
            ->find($this->jobId);

        if (! $job) {
            return;
        }

        $domain = $job->domain;

        if (! $domain || $domain->status !== Domain::STATUS_VERIFIED) {
            $this->markItemsFailed($job, 'This domain is not verified yet.');

            return;
        }

        foreach ($this->itemIds as $itemId) {
            try {
                $this->processItem($job, $domain, (int) $itemId);
            } catch (Throwable $exception) {
                $this->markItemFailedById($job, (int) $itemId, 'Unexpected error while processing this URL.');
            }
        }
    }

    private function processItem(BulkImportJob $job, Domain $domain, int $itemId): void
    {
        DB::transaction(function () use ($job, $domain, $itemId): void {
            $item = BulkImportItem::query()
                ->where('id', $itemId)
                ->where('job_id', $job->id)
                ->lockForUpdate()
                ->first();

            if (! $item) {
                return;
            }

            if (in_array($item->status, [BulkImportItem::STATUS_SUCCEEDED, BulkImportItem::STATUS_FAILED], true)) {
                return;
            }

            if ($item->link_id) {
                $this->markItemSucceeded($job, $item, Link::query()->find($item->link_id));

                return;
            }

            $source = $this->normalizeUrl($item->source_url);

            if ($source === null) {
                $this->markItemFailed($job, $item, 'Enter a valid URL.');

                return;
            }

            [$isValid, $message] = $this->validateUrl($source);

            if (! $isValid) {
                $this->markItemFailed($job, $item, $message);

                return;
            }

            $link = Link::query()->create([
                'domain_id' => $domain->id,
                'user_id' => $job->user_id,
                'code' => $this->generateCode($domain->id),
                'alias' => null,
                'link_type' => LinkType::Static,
                'destination_url' => $source,
                'fallback_destination_url' => null,
                'password_hash' => $job->default_password_hash,
                'expires_at' => $job->default_expires_at,
                'click_count' => 0,
                'last_accessed_at' => null,
                'qr_path' => null,
            ]);

            $this->markItemSucceeded($job, $item, $link);

            DB::afterCommit(static function () use ($link): void {
                event(new LinkCreated($link));
            });
        });
    }

    private function markItemsFailed(BulkImportJob $job, string $message): void
    {
        foreach ($this->itemIds as $itemId) {
            $this->markItemFailedById($job, (int) $itemId, $message);
        }
    }

    private function markItemFailedById(BulkImportJob $job, int $itemId, string $message): void
    {
        DB::transaction(function () use ($job, $itemId, $message): void {
            $item = BulkImportItem::query()
                ->where('id', $itemId)
                ->where('job_id', $job->id)
                ->lockForUpdate()
                ->first();

            if (! $item) {
                return;
            }

            $this->markItemFailed($job, $item, $message);
        });
    }

    private function markItemFailed(BulkImportJob $job, BulkImportItem $item, string $message): void
    {
        if (in_array($item->status, [BulkImportItem::STATUS_SUCCEEDED, BulkImportItem::STATUS_FAILED], true)) {
            return;
        }

        $item->forceFill([
            'status' => BulkImportItem::STATUS_FAILED,
            'link_id' => null,
            'error_message' => $message,
            'qr_status' => null,
        ])->save();

        BulkImportJob::query()->where('id', $job->id)->increment('processed_count');
        BulkImportJob::query()->where('id', $job->id)->increment('failed_count');

        DB::afterCommit(static function () use ($job, $item): void {
            $freshJob = BulkImportJob::query()->find($job->id);

            if (! $freshJob) {
                return;
            }

            event(new BulkImportJobUpdated($freshJob, [$item->id]));
        });
    }

    private function markItemSucceeded(BulkImportJob $job, BulkImportItem $item, ?Link $link): void
    {
        if (in_array($item->status, [BulkImportItem::STATUS_SUCCEEDED, BulkImportItem::STATUS_FAILED], true)) {
            return;
        }

        if (! $link) {
            $this->markItemFailed($job, $item, 'Unable to locate the generated link.');

            return;
        }

        $item->forceFill([
            'status' => BulkImportItem::STATUS_SUCCEEDED,
            'link_id' => $link->id,
            'error_message' => null,
            'qr_status' => 'queued',
        ])->save();

        BulkImportJob::query()->where('id', $job->id)->increment('processed_count');
        BulkImportJob::query()->where('id', $job->id)->increment('success_count');

        DB::afterCommit(static function () use ($job, $item): void {
            $freshJob = BulkImportJob::query()->find($job->id);

            if (! $freshJob) {
                return;
            }

            event(new BulkImportJobUpdated($freshJob, [$item->id]));
        });
    }

    private function normalizeUrl(string $url): ?string
    {
        $normalized = trim($url);

        return $normalized === '' ? null : $normalized;
    }

    /**
     * @return array{0: bool, 1: string}
     */
    private function validateUrl(string $url): array
    {
        if (! Str::startsWith($url, ['http://', 'https://'])) {
            return [false, 'URLs must start with http:// or https://.'];
        }

        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return [false, 'Enter a valid URL.'];
        }

        $host = parse_url($url, PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            return [false, 'Enter a valid URL.'];
        }

        if ($this->isBlockedIp($host)) {
            return [false, 'Destination URLs cannot target private or reserved IPs.'];
        }

        return [true, ''];
    }

    private function isBlockedIp(string $host): bool
    {
        if (! filter_var($host, FILTER_VALIDATE_IP)) {
            return false;
        }

        return ! filter_var(
            $host,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
        );
    }

    private function generateCode(int $domainId): string
    {
        $attempts = 0;

        while ($attempts < 8) {
            $attempts++;
            $code = Str::lower(Str::random(7));

            $exists = Link::query()
                ->where('domain_id', $domainId)
                ->where('code', $code)
                ->exists();

            if (! $exists) {
                return $code;
            }
        }

        throw new \RuntimeException('Unable to generate a unique short code.');
    }
}
