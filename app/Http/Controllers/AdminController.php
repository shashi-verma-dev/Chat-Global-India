<?php

namespace App\Http\Controllers;

use App\Events\AnnouncementBroadcasted;
use App\Events\ChatCleared;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    /**
     * Delete all messages if the correct secret code is provided.
     *
     * Route: POST /admin/chat/clear
     * Body:  code=ADMIN_SECRET_CODE
     *
     * Usage: curl -X POST http://localhost:8000/admin/chat/clear -d "code=your_code"
     */
    public function clearChat(Request $request): JsonResponse
    {
        if ($request->input('code') !== config('app.admin_secret_code')) {
            return response()->json(['error' => 'Invalid code.'], 403);
        }

        Message::query()->delete();

        broadcast(new ChatCleared(
            cleared_by: 'admin',
            cleared_at: now()->toIso8601String(),
        ));

        return response()->json(['ok' => true, 'message' => 'Chat cleared.']);
    }

    /**
     * Broadcast a global popup announcement to all connected users.
     *
     * Route: POST /admin/announcement
     * Body:  code=ADMIN_SECRET_CODE&message=Hello+everyone
     *
     * Usage: curl -X POST http://localhost:8000/admin/announcement \
     *             -d "code=your_code&message=Server going down in 5 min"
     */
    public function announcement(Request $request): JsonResponse
    {
        if ($request->input('code') !== config('app.admin_secret_code')) {
            return response()->json(['error' => 'Invalid code.'], 403);
        }

        $message = strip_tags(trim($request->input('message', '')));

        if (empty($message)) {
            return response()->json(['error' => 'Message is required.'], 422);
        }

        broadcast(new AnnouncementBroadcasted(
            title:   '📢 Announcement',
            body:    $message,
            timeout: 20,
        ));

        return response()->json(['ok' => true, 'message' => 'Announcement sent.']);
    }
}
