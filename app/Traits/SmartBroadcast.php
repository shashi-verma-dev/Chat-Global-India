<?php

namespace App\Traits;

/**
 * SmartBroadcast — environment-aware broadcasting strategy.
 *
 * Apply this trait to any event that implements ShouldBroadcast.
 *
 * How it works:
 *   Laravel's BroadcastManager checks if the event has a `shouldBroadcastNow()` method.
 *   - If it returns true, Laravel broadcasts the event immediately (synchronously).
 *   - If it returns false, Laravel queues the event (asynchronous background processing).
 *
 * This allows us to have instant broadcasts in local development (no queue:work required)
 * and robust, queued broadcasts in production (using Redis/SQS).
 */
trait SmartBroadcast
{
    /**
     * Determine if the event should be broadcasted immediately.
     */
    public function shouldBroadcastNow(): bool
    {
        return app()->isLocal();
    }
}
