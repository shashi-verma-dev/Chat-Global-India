<?php

namespace App\Services;

use App\Events\OnlineUsersUpdated;
use Illuminate\Support\Facades\Cache;

class PresenceService
{
    /**
     * Cache key that stores the set of active session IDs.
     */
    private const PRESENCE_KEY = 'presence:active_sessions';

    /**
     * How long (in seconds) a session is considered active without a heartbeat.
     * Set to match your frontend ping interval + a small buffer.
     */
    private const TTL_SECONDS = 120;

    /**
     * Register a session as online and broadcast the new count.
     *
     * Call this from a heartbeat endpoint or on every authenticated request.
     *
     * @param  string $sessionId
     * @return void
     */
    public function join(string $sessionId): void
    {
        $sessions = $this->getSessions();
        $sessions[$sessionId] = now()->timestamp;

        $this->putSessions($sessions);
        $this->broadcastCount();
    }

    /**
     * Mark a session as offline and broadcast the new count.
     *
     * Call this when the user disconnects (e.g. WebSocket close event).
     *
     * @param  string $sessionId
     * @return void
     */
    public function leave(string $sessionId): void
    {
        $sessions = $this->getSessions();
        unset($sessions[$sessionId]);

        $this->putSessions($sessions);
        $this->broadcastCount();
    }

    /**
     * Return the number of currently online sessions.
     *
     * Stale sessions (older than TTL) are pruned automatically before counting.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->getActiveSessions());
    }

    /**
     * Prune timed-out sessions and return the live count.
     *
     * Can be called from a scheduled command to keep the cache tidy
     * without relying solely on WebSocket disconnect events.
     *
     * @return int  Remaining active session count.
     */
    public function prune(): int
    {
        $active = $this->getActiveSessions();
        $this->putSessions($active);

        return count($active);
    }

    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Retrieve the raw session map from cache.
     *
     * @return array<string, int>  Map of sessionId → last-seen Unix timestamp.
     */
    private function getSessions(): array
    {
        return Cache::get(self::PRESENCE_KEY, []);
    }

    /**
     * Filter out sessions that have exceeded the TTL and return only active ones.
     *
     * @return array<string, int>
     */
    private function getActiveSessions(): array
    {
        $cutoff = now()->timestamp - self::TTL_SECONDS;

        return array_filter(
            $this->getSessions(),
            fn(int $lastSeen) => $lastSeen >= $cutoff,
        );
    }

    /**
     * Persist the session map back to cache with a rolling TTL.
     *
     * @param  array<string, int> $sessions
     * @return void
     */
    private function putSessions(array $sessions): void
    {
        Cache::put(self::PRESENCE_KEY, $sessions, self::TTL_SECONDS * 2);

        // Mirror the scalar count in a separate key for fast header reads.
        Cache::put('online_users_count', count($sessions), self::TTL_SECONDS * 2);
    }

    /**
     * Fire OnlineUsersUpdated so all connected clients update their header badge.
     *
     * @return void
     */
    private function broadcastCount(): void
    {
        broadcast(new OnlineUsersUpdated(count: $this->count()));
    }
}
