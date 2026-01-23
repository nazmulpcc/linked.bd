<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LinkQrGenerated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  array{previewUrl: string, downloadUrl: string}  $payload
     */
    public function __construct(
        public string $token,
        public array $payload,
    ) {}

    /**
     * The name of the event to broadcast.
     */
    public function broadcastAs(): string
    {
        return 'link.qr.generated';
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('link-qr.'.$this->token),
        ];
    }

    /**
     * @return array{previewUrl: string, downloadUrl: string}
     */
    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
