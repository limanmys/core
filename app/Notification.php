<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

/**
 * App\Notification
 *
 * @property-read mixed $id
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Notification newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Notification newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Notification query()
 * @method static \Illuminate\Database\Query\Builder|\App\Notification where($value)
 * @mixin \Eloquent
 */
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
        $notification->read = false;
        $notification->save();

        // Before we return the notification, check if it's urgent. If so, send an email.


        return $notification;
    }
}
