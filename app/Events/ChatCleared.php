<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatCleared implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  string $cleared_by  Identifier of the admin/user who cleared the chat
     *                             (e.g. "admin" or a username). Used for audit logging.
     * @param  string $cleared_at  ISO-8601 timestamp of when the clear happened.
     */
    public function __construct(
        public readonly string $cleared_by,
        public readonly string $cleared_at,
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
        return 'chat.cleared';
    }
}
