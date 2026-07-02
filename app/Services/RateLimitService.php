<?php

namespace App\Services;

use Illuminate\Cache\RateLimiter;

class RateLimitService
{
    /** Max messages per window. */
    private const MAX_MESSAGES = 40;

    /** Window length in seconds. */
    private const DECAY_SECONDS = 60;

    public function __construct(
        private readonly RateLimiter $limiter,
    ) {}

    /**
     * Returns true if the client is allowed to send a message.
     * Returns false if they have exceeded 40 messages per 60 seconds.
     *
     * @param  string $clientId  Anonymous UUID from cookie.
     * @return bool
     */
    public function allowMessage(string $clientId): bool
    {
        $key = "rate:msg:{$clientId}";

        if ($this->limiter->tooManyAttempts($key, self::MAX_MESSAGES)) {
            return false;
        }

        $this->limiter->hit($key, self::DECAY_SECONDS);

        return true;
    }

    /**
     * How many messages the client can still send in this window.
     *
     * @param  string $clientId
     * @return int
     */
    public function remaining(string $clientId): int
    {
        return $this->limiter->remaining(
            "rate:msg:{$clientId}",
            self::MAX_MESSAGES,
        );
    }

    /**
     * Clear rate-limit counters for a client (e.g. admin reset).
     *
     * @param  string $clientId
     * @return void
     */
    public function clear(string $clientId): void
    {
        $this->limiter->clear("rate:msg:{$clientId}");
    }
}
