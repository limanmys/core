<?php

namespace App\Http\Controllers;

use App\Notification;

class NotificationController extends Controller
{
    public function check(){
        $notifications = Notification::where('user_id',\Auth::id())->orderBy('updated_at','desc')->take(5)->get();
        return view('__system__.notifications',[
            "notifications" => $notifications
        ]);
    }
}
