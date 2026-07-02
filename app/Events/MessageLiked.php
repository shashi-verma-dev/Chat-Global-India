<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageLiked implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  int  $message_id  The ID of the message that was liked.
     * @param  array $reactions_count The new total reaction counts after this reaction.
     * @param  bool $is_popular  True when reactions_count exceeds the popular threshold (> 3).
     */
    public function __construct(
        public int   $message_id,
        public array $reactions_count,
        public bool  $is_popular,
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
        return 'message.liked';
    }
}
