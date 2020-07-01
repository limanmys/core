<?php

namespace App\Observers;

use App\Notification;
use App\User;
use App\Notifications\NotificationSent;

class NotificationObserver
{
    private function sendBroadcast($notification, $user_id = null)
    {
        $user = User::find(
            isset($notification->user_id) ? $notification->user_id : $user_id
        );
        $user->notify(new NotificationSent($notification));
    }

    /**
     * Handle the notification "created" event.
     *
     * @param  \App\Notification  $notification
     * @return void
     */
    public function created(Notification $notification)
    {
        $this->sendBroadcast($notification);
    }

    /**
     * Handle the notification "updated" event.
     *
     * @param  \App\Notification  $notification
     * @return void
     */
    public function updated(Notification $notification)
    {
        $this->sendBroadcast([], $notification->user_id);
    }

    /**
     * Handle the notification "deleted" event.
     *
     * @param  \App\Notification  $notification
     * @return void
     */
    public function deleted(Notification $notification)
    {
        $this->sendBroadcast([], $notification->user_id);
    }

    /**
     * Handle the notification "restored" event.
     *
     * @param  \App\Notification  $notification
     * @return void
     */
    public function restored(Notification $notification)
    {
        $this->sendBroadcast($notification);
    }

    /**
     * Handle the notification "force deleted" event.
     *
     * @param  \App\Notification  $notification
     * @return void
     */
    public function forceDeleted(Notification $notification)
    {
        $this->sendBroadcast([], $notification->user_id);
    }
}
