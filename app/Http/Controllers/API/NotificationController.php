<?php

namespace App\Http\Controllers\API;

use App\Classes\NotificationBuilder;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Notification Controller
 */
class NotificationController extends Controller
{
    /**
     * Return all notifications that user owns
     *
     * @return mixed
     */
    public function index()
    {
        return auth('api')->user()
            ->notifications()
            ->withPivot('read_at', 'seen_at')
            ->orderBy('send_at', 'desc')
            ->take(100)
            ->get()
            ->map(function ($notification) {
                $builder = new NotificationBuilder($notification, auth('api')->user()->locale);

                return $builder->convertToBroadcastable();
            });
    }

    /**
     * Return unread notifications
     *
     * @return mixed
     */
    public function unread()
    {
        return auth('api')->user()
            ->notifications()
            ->withPivot('read_at', 'seen_at')
            ->where('read_at', null)
            ->orderBy('send_at', 'desc')
            ->take(8)
            ->get()
            ->map(function ($notification) {
                $builder = new NotificationBuilder($notification, auth('api')->user()->locale);

                return $builder->convertToBroadcastable();
            });
    }

    /**
     * Mark notification as seen
     *
     * @param Request $request
     * @return mixed
     */
    public function seen(Request $request)
    {
        $notification = auth()->user()
            ->notifications()
            ->where('id', $request->notification_id)
            ->withPivot('read_at', 'seen_at')
            ->first();

        if ($notification) {
            $notification->pivot->seen_at = now();
            $notification->pivot->save();
        }

        return $notification;
    }

    /**
     * Mark all as read
     *
     * @return JsonResponse
     */
    public function read()
    {
        auth()->user()
            ->notifications()
            ->where('read_at', null)
            ->withPivot('read_at', 'seen_at')
            ->get()
            ->map(function ($notification) {
                $notification->pivot->seen_at = now();
                $notification->pivot->read_at = now();
                $notification->pivot->save();
            });

        return response()->json([
            'message' => 'Bildirimler okundu olarak işaretlendi.'
        ]);
    }
}
