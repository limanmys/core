<?php

namespace App\Http\Controllers\Notification;

use App\AdminNotification;
use App\Notification;
use App\Http\Controllers\Controller;
use App\Notifications\NotificationSent;
use App\User;

class MainController extends Controller
{

    public function all()
    {
        $notifications = Notification::where([
            "user_id" => auth()->id()
        ])->orderBy('read')->orderBy('created_at', 'desc')->get();
        return response()->view('notification.index', [
            "notifications" => $notifications,
            "system" => false
        ]);

    }
    public function delete()
    {
        $notification = Notification::where([
            "user_id" => auth()->id(),
            "id" => request('notification_id')
        ])->first();
        $notification->delete();
        return respond("Bildirim silindi.");
    }

    public function delete_read()
    {
        Notification::where([
            "user_id" => auth()->id(),
            "read" => true
        ])->delete();
        return respond("Bildirimler silindi.");
    }

    public function check()
    {
        $notifications = Notification::where([
            "user_id" => auth()->id(),
            "read" => false
        ])->orderBy('updated_at', 'desc')->get();
        $adminNotifications = [];
        if(auth()->user()->isAdmin()){
            $adminNotifications = AdminNotification::where([
                "read" => "false"
            ])->orderBy('updated_at', 'desc')->get();
        }
        return respond([
            "user" => $notifications,
            "admin" => $adminNotifications
        ]);
    }

    public function read()
    {
        $notification = Notification::where([
            "user_id" => auth()->id(),
            "id" => request('notification_id')
        ])->first();
        if (!$notification) {
            return respond("Bildirim Bulunamadi", 201);
        }
        $notification->read = true;
        $notification->save();
        return $notification->id;
    }

    public function readAll()
    {
        Notification::where([
            "user_id" => auth()->id(),
        ])->update([
            "read" => true
        ]);
        auth()->user()->notify(new NotificationSent([]));
        return respond("Hepsi Okundu", 200);
    }

    public function adminRead()
    {
        AdminNotification::where([
            "read" => "false"
        ])->update([
            "read" => "true"
        ]);
        $adminUsers = User::where('status', 1)->get();
        foreach($adminUsers as $user){
            $user->notify(new NotificationSent([]));
        }
        return respond("Hepsi Okundu.",200);
    }

    public function allSystem()
    {
        $notifications = AdminNotification::orderBy('read')->orderBy('created_at', 'desc')->get();
        return response()->view('notification.index', [
            "notifications" => $notifications,
            "system" => true
        ]);
    }

}
