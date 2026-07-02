<?php

namespace App\Http\Controllers;

use App\Events\MessageCreated;
use App\Http\Requests\StoreMessageRequest;
use App\Models\Message;
use App\Services\AnonymousClientService;
use App\Services\BadWordFilterService;
use App\Services\RateLimitService;
use App\Services\XssProtectionService;
use Illuminate\Http\JsonResponse;

class MessageController extends Controller
{
    public function __construct(
        private readonly AnonymousClientService $client,
        private readonly XssProtectionService   $xss,
        private readonly BadWordFilterService   $badWords,
        private readonly RateLimitService       $rateLimiter,
    ) {}

    /**
     * Store a new chat message and broadcast it.
     *
     * Route: POST /messages
     * Returns JSON so the frontend can handle popup messages on errors.
     */
    public function store(StoreMessageRequest $request): JsonResponse
    {
        $clientId = $this->client->getOrCreateClientId();
        $raw      = $request->validated('message');

        // ── 1. XSS / script injection check ──────────────────────────────
        if ($this->xss->containsXssPattern($raw)) {
            return response()->json([
                'popup' => 'etni coding to hame bhi aati hai darling',
            ], 422);
        }

        // ── 2. Rate limit: 40 messages per 60 seconds ─────────────────────
        if (! $this->rateLimiter->allowMessage($clientId)) {
            return response()->json([
                'popup' => 'are bass kar ja bhai',
            ], 429);
        }

        // ── 3. Sanitise + filter bad words ───────────────────────────────
        $clean = $this->badWords->filter(
            $this->xss->sanitise($raw)
        );

        // ── 4. Persist ────────────────────────────────────────────────────
        $message = Message::create([
            'client_id'       => $clientId,
            'guest_name'      => $this->client->buildGuestName($clientId),
            'message'         => $clean,
            'reactions_count' => [],
        ]);

        // ── 5. Broadcast to all clients ───────────────────────────────────
        broadcast(new MessageCreated(
            id:              $message->id,
            guest_name:      $message->guest_name,
            message:         $message->message,
            reactions_count: $message->reactions_count ?? [],
            created_at:      $message->created_at->format('h:i A'),
        ));

        // ── 6. Trim to last 100 messages ──────────────────────────────────
        $this->trimMessages();

        return response()->json(['ok' => true]);
    }

    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Delete messages older than the most-recent 100.
     * Uses a single subquery to avoid loading all IDs into PHP.
     */
    private function trimMessages(): void
    {
        $count = Message::count();

        if ($count > 100) {
            $keepIds = Message::latest()
                ->take(100)
                ->pluck('id');

            Message::whereNotIn('id', $keepIds)->delete();
        }
    }
}
