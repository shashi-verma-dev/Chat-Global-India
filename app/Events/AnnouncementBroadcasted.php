<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Traits\SmartBroadcast;

class AnnouncementBroadcasted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels, SmartBroadcast;

    /**
     * Create a new event instance.
     *
     * @param  string $title   Bold heading shown in the announcement overlay.
     * @param  string $body    Supporting message shown below the title.
     * @param  int    $timeout Seconds before the overlay auto-dismisses (default 5).
     */
    public function __construct(
        public readonly string $title,
        public readonly string $body,
        public readonly int    $timeout = 5,
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * Announcement is pushed on the public global-chat channel so every
     * visitor — authenticated or not — receives it instantly.
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
        return 'announcement.broadcasted';
    }
}
