<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Traits\SmartBroadcast;

class OnlineUsersUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels, SmartBroadcast;

    /**
     * Create a new event instance.
     *
     * @param  int $count The current number of online users/connections.
     */
    public function __construct(
        public readonly int $count,
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * Uses the public global-chat channel so unauthenticated visitors
     * also receive live online-count updates in the header.
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
        return 'online-users.updated';
    }
}
