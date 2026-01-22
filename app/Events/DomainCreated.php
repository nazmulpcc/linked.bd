<?php

namespace App\Events;

use App\Models\Domain;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DomainCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Domain $domain) {}
}
