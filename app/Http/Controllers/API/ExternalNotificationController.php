<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ExternalNotification;
use App\Models\Notification;
use Illuminate\Http\Request;

class ExternalNotificationController extends Controller
{
    public function accept(Request $request)
    {
        $channel = ExternalNotification::where('token', $request->token)
            ->first();

        // If token not found, return 404 error
        if (! $channel) {
            return response()->json([
                'status' => false,
                'message' => 'token is missing'
            ], 404);
        }

        // If IP not in range, return 403 error
        if (! ip_in_range($request->ip(), $channel->ip)) {
            return response()->json([
                'status' => false,
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
            'status' => (bool) $notification,
            'notification' => $notification
        ], (bool) $notification ? 200 : 500);
    }
}
