<?php

namespace App\Events;

use App\Models\BulkImportItem;
use App\Models\BulkImportJob;
use App\Services\BulkImportPayloadBuilder;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Date;

class BulkImportJobUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  array<int, int>  $itemIds
     */
    public function __construct(public BulkImportJob $job, public array $itemIds = []) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('bulk-imports.'.$this->job->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'bulk.import.updated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $builder = app(BulkImportPayloadBuilder::class);
        $job = $this->job->fresh();

        if (! $job) {
            return [];
        }

        $items = [];

        if ($this->itemIds !== []) {
            $items = BulkImportItem::query()
                ->whereIn('id', $this->itemIds)
                ->with('link.domain')
                ->get()
                ->all();
        }

        $lastUpdatedAt = collect($items)
            ->pluck('updated_at')
            ->push($job->updated_at)
            ->filter()
            ->max();

        return [
            'job' => $builder->jobPayload($job),
            'items' => $builder->itemsPayload($items),
            'last_updated_at' => $lastUpdatedAt
                ? Date::parse($lastUpdatedAt)->toIso8601String()
                : null,
        ];
    }
}
