<?php

namespace App\Http\Controllers\Notification;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use App\Models\Notification;
use App\Notifications\NotificationSent;
use App\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * Notification Controller
 *
 * @extends Controller
 */
class MainController extends Controller
{
    /**
     * Get notification list
     *
     * @return JsonResponse|Response
     */
    public function all()
    {
        $notifications = Notification::where([
            'user_id' => auth()->id(),
        ])
            ->orderBy('read')
            ->orderBy('created_at', 'desc')
            ->paginate(10);
        $links = $notifications->links();
        $notifications = $notifications->groupBy(function ($date) {
            return \Carbon\Carbon::parse($date->created_at)->format('d.m.Y');
        });

        return magicView('notification.index', [
            'notifications' => $notifications,
            'links' => $links,
            'system' => false,
        ]);
    }

    /**
     * Remove read notifications
     *
     * @return JsonResponse|Response
     */
    public function delete_read()
    {
        Notification::where([
            'user_id' => auth()->id(),
            'read' => true,
        ])->delete();

        return respond('Bildirimler silindi.');
    }

    /**
     * Delete specific notification
     *
     * Send notification_id on request body to delete
     *
     * @return JsonResponse|Response
     */
    public function delete()
    {
        $notification = Notification::where([
            'user_id' => auth()->id(),
            'id' => request('notification_id'),
        ])->first();
        $notification->delete();

        return respond('Bildirim silindi.');
    }

    /**
     * Retrieve new notifications
     *
     * @return JsonResponse|Response
     */
    public function check()
    {
        $notifications = Notification::where([
            'user_id' => auth()->id(),
            'read' => false,
        ])
            ->orderBy('updated_at', 'desc')
            ->get()
            ->each(function ($item, $key) {
                $humanDate = $item->created_at->diffForHumans();
                $item->humanDate = $humanDate;
            });

        $adminNotifications = [];
        if (
            auth()
                ->user()
                ->isAdmin()
        ) {
            $adminNotifications = AdminNotification::where([
                'read' => 'false',
            ])
                ->orderBy('updated_at', 'desc')
                ->get()
                ->each(function ($item, $key) {
                    $humanDate = $item->created_at->diffForHumans();
                    $item->humanDate = $humanDate;
                });
        }

        return respond([
            'user' => $notifications,
            'admin' => $adminNotifications,
        ]);
    }

    /**
     * Read notification
     *
     * Send notification_id on request body to read notification
     *
     * @return JsonResponse|Response
     */
    public function read()
    {
        $notification = Notification::where([
            'id' => request('notification_id'),
        ])->first();

        if (! $notification) {
            $notification = AdminNotification::where([
                'id' => request('notification_id'),
            ])->first();
        }

        if (! $notification) {
            return respond('Bildirim Bulunamadi', 201);
        }
        $notification->update([
            'read' => true,
        ]);

        return $notification->id;
    }

    /**
     * Read all notifications
     *
     * @return JsonResponse|Response
     */
    public function readAll()
    {
        Notification::where([
            'user_id' => auth()->id(),
        ])->update([
            'read' => true,
        ]);
        auth()
            ->user()
            ->notify(new NotificationSent([]));

        return respond('Hepsi Okundu', 200);
    }

    /**
     * Read all admin notifications
     *
     * @return JsonResponse|Response
     */
    public function adminRead()
    {
        AdminNotification::where([
            'read' => 'false',
        ])->update([
            'read' => 'true',
        ]);
        $adminUsers = User::where('status', 1)->get();
        foreach ($adminUsers as $user) {
            $user->notify(new NotificationSent([]));
        }

        return respond('Hepsi Okundu.', 200);
    }

    /**
     * Retrieve all system notifications
     *
     * @return JsonResponse|Response
     */
    public function allSystem()
    {
        $notifications = AdminNotification::orderBy('read')
            ->orderBy('created_at', 'desc')->paginate(10);

        $links = $notifications->links();
        $notifications = $notifications->groupBy(function ($date) {
            return \Carbon\Carbon::parse($date->created_at)->format('d.m.Y');
        });

        return magicView('notification.index', [
            'notifications' => $notifications,
            'links' => $links,
            'system' => true,
        ]);
    }
}
