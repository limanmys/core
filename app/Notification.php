<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

/**
 * App\Notification
 *
 * @property-read mixed $id
 * @method static Builder|Notification newModelQuery()
 * @method static Builder|Notification newQuery()
 * @method static Builder|Notification query()
 * @method static \Illuminate\Database\Query\Builder|Notification where($value)
 * @mixin Eloquent
 */
class Notification extends Model
{
    use UsesUuid;
    public static function new($title, $type, $message, $server_id = null, $extension_id = null, $level = 0){

        // Create a notification object and fill values.
        $notification = new Notification();
        $notification->user_id = auth()->id();
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
    public static function send($title, $type, $message, $user_id, $server_id = null, $extension_id = null, $level = 0){
        // Create a notification object and fill values.
        $notification = new Notification();
        $notification->user_id = $user_id;
        $notification->title = $title;
        $notification->type = $type;
        $notification->message = $message;
        $notification->server_id = $server_id;
        $notification->extension_id = $extension_id;
        $notification->level = $level;
        $notification->read = false;
        $notification->save();
    }
}
