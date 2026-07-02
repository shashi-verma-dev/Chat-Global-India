<?php

namespace App\Services;

use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Cookie;

class AnonymousClientService
{
    /**
     * Cookie name that stores the anonymous client UUID.
     * Long-lived (1 year) so the same browser always has the same identity
     * across tabs and sessions — critical for duplicate-like prevention.
     */
    private const COOKIE_NAME = 'chat_client_id';

    /**
     * Cookie lifetime in minutes (1 year).
     */
    private const COOKIE_MINUTES = 525_600;

    /**
     * Return the client UUID for the current request.
     * If no cookie exists yet, a new UUID is generated and queued
     * for attachment to the response via cookie()->queue().
     *
     * @return string  UUID v4
     */
    public function getOrCreateClientId(): string
    {
        $id = request()->cookie(self::COOKIE_NAME);

        if (! $id || ! Str::isUuid($id)) {
            $id = (string) Str::uuid();
            $this->queueCookie($id);
        }

        return $id;
    }

    /**
     * Generate an anonymous guest name in the form "Guest NNN".
     *
     * The number is seeded from the first 6 hex chars of the UUID
     * so the same client always gets the same number — making messages
     * feel consistent without storing anything server-side.
     *
     * @param  string $clientId  UUID from getOrCreateClientId()
     * @return string            e.g. "Guest 428"
     */
    public function buildGuestName(string $clientId): string
    {
        $hex    = substr(str_replace('-', '', $clientId), 0, 6);
        $number = (hexdec($hex) % 900) + 100; // always 100–999

        return 'Guest ' . $number;
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function queueCookie(string $id): void
    {
        cookie()->queue(
            cookie(self::COOKIE_NAME, $id, self::COOKIE_MINUTES, '/', null, false, true)
        );
    }
}
