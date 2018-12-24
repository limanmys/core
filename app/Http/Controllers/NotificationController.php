<?php

namespace App\Http\Controllers;

use App\Notification;

class NotificationController extends Controller
{
    public function check(){
        $notifications = Notification::where([
            "user_id" => \Auth::id(),
            "read" => false
        ])->orderBy('updated_at','desc')->take(5)->get();
        return view('__system__.notifications',[
            "notifications" => $notifications
        ]);
    }

    public function read(){
        $notification = Notification::where([
            "user_id" => \Auth::id(),
            "_id" => request('notification_id')
        ])->first();
        $notification->read = true;
        $notification->save();
        return $notification->_id;
    }
}
