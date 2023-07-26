<?php

namespace App\Http\Controllers\API;

use App\Exceptions\JsonResponseException;
use App\Http\Controllers\Controller;
use App\Models\ExternalNotification;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * External Notification Controller
 *
 * Accepts external notifications
 */
class ExternalNotificationController extends Controller
{
    /**
     * Accepts external notifications from outside
     *
     * @param Request $request
     * @return JsonResponse
     * @throws JsonResponseException
     */
    public function accept(Request $request): JsonResponse
    {
        $channel = ExternalNotification::where('token', $request->token)
            ->first();

        // If token not found, return 404 error
        if (! $channel) {
            return response()->json([
                'message' => 'token is missing'
            ], 404);
        }

        // If IP not in range, return 403 error
        if (! ip_in_range($request->ip(), $channel->ip)) {
            return response()->json([
                'message' => 'ip is not in range'
            ], 403);
        }

        validate([
            'title' => 'required',
            'content' => 'required',
            'level' => 'required',
        ]);

        $notification = Notification::send(
            $request->level,
            "CUSTOM",
            [
                "title" => $request->title,
                "content" => $request->content,
            ],
            $request->send_to,
            $request->mail
        );

        $channel->update([
            'last_used' => now()
        ]);

        return response()->json([
            'notification' => $notification
        ], (bool) $notification ? 200 : 500);
    }
}
