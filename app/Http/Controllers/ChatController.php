<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Services\AnonymousClientService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChatController extends Controller
{
    public function __construct(
        private readonly AnonymousClientService $client,
    ) {}

    /**
     * Display the global chat page.
     *
     * Route: GET /
     */
    public function index(): View
    {
        // Load last 100 messages in chronological order (oldest first).
        $messages = Message::latest()
            ->take(100)
            ->get()
            ->reverse()
            ->values();

        // Online user count from Redis cache (written by PresenceService).
        $onlineUsers = cache()->get('online_users_count', 1);

        // Client UUID for JS (used for socket ID and like dedup).
        $clientId = $this->client->getOrCreateClientId();

        return view('chat', compact('messages', 'onlineUsers', 'clientId'));
    }

    /**
     * Heartbeat endpoint — keeps the visitor in the presence tracker.
     *
     * Route: POST /heartbeat
     * Called by the frontend every 30 seconds.
     */
    public function heartbeat(Request $request, \App\Services\PresenceService $presence): JsonResponse
    {
        $clientId = $this->client->getOrCreateClientId();

        // Register session as online and broadcast the new count
        $presence->join($clientId);

        return response()->json(['online' => $presence->count()]);
    }
}
