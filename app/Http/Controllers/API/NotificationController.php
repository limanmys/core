<?php

namespace App\Http\Controllers\API;

use App\Classes\NotificationBuilder;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = auth()->user()
            ->notifications()
            ->withPivot('read_at', 'seen_at')
            ->orderBy('send_at', 'desc')
            ->take(100)
            ->get()
            ->map(function ($notification) {
                $builder = new NotificationBuilder($notification);

                return $builder->convertToBroadcastable();
            });

        return response()->json($notifications);
    }

    public function unread()
    {
        $notifications = auth()->user()
            ->notifications()
            ->withPivot('read_at', 'seen_at')
            ->unread()
            ->orderBy('send_at', 'desc')
            ->take(8)
            ->get()
            ->map(function ($notification) {
                $builder = new NotificationBuilder($notification);

                return $builder->convertToBroadcastable();
            });

        return response()->json($notifications);
    }

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

        return response()->json($notification);
    }

    public function read()
    {
        auth()->user()
            ->notifications()
            ->unread()
            ->withPivot('read_at', 'seen_at')
            ->get()
            ->map(function ($notification) {
                $notification->pivot->seen_at = now();
                $notification->pivot->read_at = now();
                $notification->pivot->save();
            });

        return response()->json(['status' => true]);
    }
}
