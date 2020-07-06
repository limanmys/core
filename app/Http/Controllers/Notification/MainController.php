<?php

namespace App\Http\Controllers\Notification;

use App\Models\AdminNotification;
use App\Models\Notification;
use App\Http\Controllers\Controller;
use App\Notifications\NotificationSent;
use App\Models\User;

class MainController extends Controller
{
    /**
     * @api {get} /bildirimler Get User Notifications
     * @apiName Get User Notifications
     * @apiGroup Notification
     *
     * @apiSuccess {Array} notifications getNotifications
     */
    public function all()
    {
        $notifications = Notification::where([
            "user_id" => auth()->id(),
        ])
            ->orderBy('read')
            ->orderBy('created_at', 'desc')
            ->get();
        $notifications = $notifications->groupBy(function ($date) {
            return \Carbon\Carbon::parse($date->created_at)->format("d.m.Y");
        });

        return magicView('notification.index', [
            "notifications" => $notifications,
            "system" => false,
        ]);
    }

    /**
     * @api {post} /bildirim/sil Remove Notification
     * @apiName Remove Notification
     * @apiGroup Notification
     *
     * @apiParam {String} notification_id ID of the notification
     *
     * @apiSuccess {JSON} message Message with status.
     */
    public function delete()
    {
        $notification = Notification::where([
            "user_id" => auth()->id(),
            "id" => request('notification_id'),
        ])->first();
        $notification->delete();
        return respond("Bildirim silindi.");
    }

    /**
     * @api {post} /bildirim/okunanlar/sil Remove Read Notifications
     * @apiName Remove Read Notifications
     * @apiGroup Notification
     *
     * @apiSuccess {JSON} message Message with status.
     */
    public function delete_read()
    {
        Notification::where([
            "user_id" => auth()->id(),
            "read" => true,
        ])->delete();
        return respond("Bildirimler silindi.");
    }

    /**
     * @api {post} /bildirimler Get New Notifications
     * @apiName Get New Notifications
     * @apiGroup Notification
     *
     * @apiSuccess {Array} user User Notifications
     * @apiSuccess {Array} admin Admin Notifications
     */
    public function check()
    {
        $notifications = Notification::where([
            "user_id" => auth()->id(),
            "read" => false,
        ])
            ->orderBy('updated_at', 'desc')
            ->get();
        $adminNotifications = [];
        if (
            auth()
                ->user()
                ->isAdmin()
        ) {
            $adminNotifications = AdminNotification::where([
                "read" => "false",
            ])
                ->orderBy('updated_at', 'desc')
                ->get();
        }
        return respond([
            "user" => $notifications,
            "admin" => $adminNotifications,
        ]);
    }

    /**
     * @api {post} /bildirim/oku Read Notification
     * @apiName Read Notification
     * @apiGroup Notification
     *
     * @apiParam {String} notification_id ID of the notification
     *
     * @apiSuccess {String} string ID of the notification
     */
    public function read()
    {
        $notification = Notification::where([
            "user_id" => auth()->id(),
            "id" => request('notification_id'),
        ])->first();
        if (!$notification) {
            return respond("Bildirim Bulunamadi", 201);
        }
        $notification->update([
            "read" => true,
        ]);
        return $notification->id;
    }

    /**
     * @api {post} /bildirimler/oku Read All Notifications
     * @apiName Read All Notifications
     * @apiGroup Notification
     *
     * @apiSuccess {JSON} message Message with status.
     */
    public function readAll()
    {
        Notification::where([
            "user_id" => auth()->id(),
        ])->update([
            "read" => true,
        ]);
        auth()
            ->user()
            ->notify(new NotificationSent([]));
        return respond("Hepsi Okundu", 200);
    }

    /**
     * @api {post} /bildirim/adminOku Read All Admin Notifications
     * @apiName Read All Admin Notifications
     * @apiGroup Notification
     *
     * @apiSuccess {JSON} message Message with status.
     */
    public function adminRead()
    {
        AdminNotification::where([
            "read" => "false",
        ])->update([
            "read" => "true",
        ]);
        $adminUsers = User::where('status', 1)->get();
        foreach ($adminUsers as $user) {
            $user->notify(new NotificationSent([]));
        }
        return respond("Hepsi Okundu.", 200);
    }

    /**
     * @api {get} /bildirimlerSistem Get Admin Notifications
     * @apiName Get Admin Notifications
     * @apiGroup Notification
     *
     * @apiSuccess {Array} notifications getNotifications
     */
    public function allSystem()
    {
        $notifications = AdminNotification::orderBy('read')
            ->orderBy('created_at', 'desc')
            ->get();

        $notifications = $notifications->groupBy(function ($date) {
            return \Carbon\Carbon::parse($date->created_at)->format("d.m.Y");
        });

        return magicView('notification.index', [
            "notifications" => $notifications,
            "system" => true,
        ]);
    }
}
