<?php

namespace App\Events;

use App\Models\Link;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LinkCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Link $link) {}
}
