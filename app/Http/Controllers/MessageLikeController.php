<?php

namespace App\Http\Controllers;

use App\Events\MessageLiked;
use App\Models\Message;
use App\Models\MessageLike;
use App\Services\AnonymousClientService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageLikeController extends Controller
{
    public function __construct(
        private readonly AnonymousClientService $client,
    ) {}

    /**
     * Toggle a like on the given message.
     *
     * Route: POST /messages/{message}/like
     * Returns JSON so the frontend can show the duplicate-like popup.
     */
    public function store(Request $request, Message $message): JsonResponse
    {
        $clientId = $this->client->getOrCreateClientId();
        $type     = $request->input('type', '👍'); // default emoji if none passed

        // ── Duplicate like check ──────────────────────────────────────────
        $alreadyLiked = MessageLike::where('message_id', $message->id)
            ->where('client_id', $clientId)
            ->where('type', $type)
            ->exists();

        if ($alreadyLiked) {
            return response()->json([
                'popup' => 'ab kya dass baar like karega',
            ], 422);
        }

        // ── Record like ───────────────────────────────────────────────────
        MessageLike::create([
            'message_id' => $message->id,
            'client_id'  => $clientId,
            'type'       => $type,
        ]);

        // Update JSON reactions column
        $reactions = $message->reactions_count ?? [];
        $reactions[$type] = ($reactions[$type] ?? 0) + 1;
        $message->reactions_count = $reactions;
        $message->save();

        // ── Broadcast to all clients ──────────────────────────────────────
        broadcast(new MessageLiked(
            message_id:      $message->id,
            reactions_count: $message->reactions_count,
            is_popular:      $message->isPopular(),
        ));

        return response()->json([
            'ok'              => true,
            'reactions_count' => $message->reactions_count,
            'is_popular'      => $message->isPopular(),
        ]);
    }
}
