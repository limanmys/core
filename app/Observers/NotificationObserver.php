<?php

namespace App\Observers;

use App\Models\Notification;
use App\Notifications\NotificationSent;

class NotificationObserver
{
    public $afterCommit = true;

    /**
     * Send broadcast for notification event
     *
     * @param $notification
     * @return void
     */
    private function sendBroadcast(Notification $notification)
    {
        $users = $notification->users()->get();

        foreach ($users as $user) {
            $user->notify(new NotificationSent($notification, $user));
        }
    }

    /**
     * Handle the notification "created" event.
     *
     * @return void
     */
    public function created(Notification $notification)
    {
        $this->sendBroadcast($notification);
    }
}
