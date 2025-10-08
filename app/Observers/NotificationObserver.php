<?php

namespace App\Observers;

use App\Mail\BasicNotification;
use App\Models\Notification;
use App\Notifications\NotificationSent;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\TransportException;
use Illuminate\Support\Facades\Log;

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
            Log::info('Notification sent.', [
                'user_id' => $user->id,
                'oidc_sub' => $user->oidc_sub ?? '',
                'notification_id' => $notification->id,
                'send_at' => $notification->send_at
            ]);
            if (env('MAIL_ENABLED') && $notification && $notification->mail) {
                try {
                    Mail::to($user)->send(new BasicNotification($notification, $user));
                } catch (TransportException $e) {
                    // Don't throw anything on when mail server is not active
                }
            }
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
