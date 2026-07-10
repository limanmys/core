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
     * @throws JsonResponseException
     */
    public function accept(Request $request): JsonResponse
    {
        $channel = ExternalNotification::where('token', $request->token)
            ->first();

        // Return the same error for both invalid token and IP mismatch
        // to prevent a differential 404/403 token enumeration oracle.
        if (! $channel || ! ip_in_range($request->ip(), $channel->ip)) {
            return response()->json([
                'message' => 'Yetkisiz erişim.',
            ], 403);
        }

        if ($request->has('level')) {
            // If level is information and success, change request data to trivial
            if ($request->level === 'information' || $request->level === 'success') {
                $request->merge([
                    'level' => 'trivial',
                ]);
            }

            // If level is warning, change request data to medium
            if ($request->level === 'warning') {
                $request->merge([
                    'level' => 'medium',
                ]);
            }

            // If level is error, change request data to high
            if ($request->level === 'error') {
                $request->merge([
                    'level' => 'critical',
                ]);
            }
        }

        validate([
            'title' => 'required',
            'content' => 'required',
            'level' => 'required|in:critical,high,medium,low,trivial',
            'send_to' => 'nullable|in:all,admins,non_admins',
            'mail' => 'nullable|boolean',
        ]);

        $notification = Notification::send(
            $request->level,
            'CUSTOM',
            [
                'title' => $request->title,
                'content' => $request->content,
            ],
            $request->send_to ?? 'all',
            (bool) $request->mail
        );

        $channel->update([
            'last_used' => now(),
        ]);

        return response()->json([
            'notification' => $notification,
        ], (bool) $notification ? 200 : 500);
    }
}
