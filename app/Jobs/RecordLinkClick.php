<?php

namespace App\Jobs;

use App\Models\Link;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RecordLinkClick implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $linkId) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $link = Link::query()->find($this->linkId);

        if (! $link) {
            return;
        }

        if ($link->expires_at && $link->expires_at->isPast()) {
            return;
        }

        $link->increment('click_count');
        $link->forceFill([
            'last_accessed_at' => now(),
        ])->save();
    }
}
