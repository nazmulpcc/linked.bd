<?php

namespace App\Jobs;

use App\Models\Link;
use App\Models\LinkVisit;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RecordLinkClick implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    /**
     * @param  array{visited_at: string, referrer_host: string|null, device_type: string|null, browser: string|null, country_code: string|null, user_agent: string|null, link_rule_id: int|null, resolved_destination_url: string|null}  $visitData
     */
    public function __construct(
        public int $linkId,
        public array $visitData,
    ) {}

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

        $this->storeVisit($link);
    }

    private function storeVisit(Link $link): void
    {
        LinkVisit::query()->create([
            'link_id' => $link->id,
            'link_rule_id' => $this->visitData['link_rule_id'] ?? null,
            'visited_at' => $this->visitData['visited_at'],
            'referrer_host' => $this->visitData['referrer_host'],
            'device_type' => $this->visitData['device_type'],
            'browser' => $this->visitData['browser'],
            'country_code' => $this->visitData['country_code'],
            'resolved_destination_url' => $this->visitData['resolved_destination_url'] ?? null,
            'user_agent' => $this->visitData['user_agent'],
        ]);
    }
}
