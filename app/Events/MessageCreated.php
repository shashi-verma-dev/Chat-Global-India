<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Traits\SmartBroadcast;

class MessageCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels, SmartBroadcast;


    /**
     * Create a new event instance.
     *
     * @param  int    $id          The message ID.
     * @param  string $guest_name  The anonymous guest name (e.g. "Guest 428").
     * @param  string $message     The message text.
     * @param  array  $reactions_count The initial reaction counts.
     * @param  string $created_at  Human-readable timestamp (e.g. "10:45 PM").
     */
    public function __construct(
        public readonly int    $id,
        public readonly string $guest_name,
        public readonly string $message,
        public readonly array  $reactions_count,
        public readonly string $created_at,
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('global-chat'),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'message.created';
    }
}
