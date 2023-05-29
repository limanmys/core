<?php

namespace App\Observers;

use App\Mail\BasicNotification;
use App\Models\Notification;
use App\Notifications\NotificationSent;
use App\User;
use Illuminate\Support\Facades\Mail;

/**
 * Notification Observer
 */
class NotificationObserver
{
    /**
     * Handle the notification "created" event.
     *
     * @return void
     */
    public function created(Notification $notification)
    {
        $this->sendBroadcast($notification);
    }

    /**
     * Send broadcast for notification event
     *
     * @param $notification
     * @param $user_id
     * @return void
     */
    private function sendBroadcast($notification, $user_id = null)
    {
        $user = User::find(
            isset($notification->user_id) ? $notification->user_id : $user_id
        );
        $user->notify(new NotificationSent($notification));
        if (env('MAIL_ENABLED') == true && $notification && $notification->type == 'external_notification') {
            if (
                isset($notification->mail) &&
                !filter_var($notification->mail, FILTER_VALIDATE_BOOLEAN)
            ) {
                return;
            }
            Mail::to($user)->send(new BasicNotification($notification));
        }
    }

    /**
     * Handle the notification "updated" event.
     *
     * @return void
     */
    public function updated(Notification $notification)
    {
        $this->sendBroadcast([], $notification->user_id);
    }

    /**
     * Handle the notification "deleted" event.
     *
     * @return void
     */
    public function deleted(Notification $notification)
    {
        $this->sendBroadcast([], $notification->user_id);
    }

    /**
     * Handle the notification "restored" event.
     *
     * @return void
     */
    public function restored(Notification $notification)
    {
        $this->sendBroadcast($notification);
    }

    /**
     * Handle the notification "force deleted" event.
     *
     * @return void
     */
    public function forceDeleted(Notification $notification)
    {
        $this->sendBroadcast([], $notification->user_id);
    }
}
