<?php

namespace App\Listeners;

use App\Events\LinkCreated;
use App\Jobs\GenerateQrForLink;

class DispatchLinkQrGeneration
{
    /**
     * Handle the event.
     */
    public function handle(LinkCreated $event): void
    {
        GenerateQrForLink::dispatch($event->link->id);
    }
}
