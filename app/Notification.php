<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class Notification extends Eloquent
{
    protected $collection = 'notifications';
    protected $connection = 'mongodb';

    public static function new($title, $type, $message, $server_id = null, $extension_id = null, $level = 0){

        // Create a notification object and fill values.
        $notification = new Notification();
        $notification->user_id = \Auth::id();
        $notification->title = $title;
        $notification->type = $type;
        $notification->message = $message;
        $notification->server_id = $server_id;
        $notification->extension_id = $extension_id;
        $notification->level = $level;
        $notification->save();

        // Before we return the notification, check if it's urgent. If so, send an email.


        return $notification;
    }
}
