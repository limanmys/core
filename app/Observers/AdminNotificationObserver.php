<?php

namespace App\Observers;

use App\Mail\BasicNotification;
use App\Models\AdminNotification;
use App\Notifications\NotificationSent;
use App\User;
use Illuminate\Support\Facades\Mail;

/**
 * Admin Notification Observer
 */
class AdminNotificationObserver
{
    /**
     * Handle the admin notification "created" event.
     *
     * @return void
     */
    public function created(AdminNotification $adminNotification)
    {
        $this->sendBroadcast($adminNotification);
    }

    /**
     * Send notification as broadcast
     *
     * @param $adminNotification
     * @return void
     */
    private function sendBroadcast($adminNotification)
    {
        $adminUsers = User::where('status', 1)->get();
        for($i = 0; $i < count($adminUsers); $i++) {
            $adminUsers[$i]->notify(new NotificationSent($adminNotification));
            if (env('MAIL_ENABLED') == true && $adminNotification && $adminNotification->type == 'external_notification') {
                if (isset($adminNotification->mail) && ! filter_var($adminNotification->mail, FILTER_VALIDATE_BOOLEAN)) {
                    continue;
                }
                Mail::to($adminUsers[$i])->send(new BasicNotification($adminNotification));
            }
        }
    }

    /**
     * Handle the admin notification "updated" event.
     *
     * @return void
     */
    public function updated(AdminNotification $adminNotification)
    {
        $this->sendBroadcast([]);
    }

    /**
     * Handle the admin notification "deleted" event.
     *
     * @return void
     */
    public function deleted(AdminNotification $adminNotification)
    {
        $this->sendBroadcast([]);
    }

    /**
     * Handle the admin notification "restored" event.
     *
     * @return void
     */
    public function restored(AdminNotification $adminNotification)
    {
        $this->sendBroadcast([]);
    }

    /**
     * Handle the admin notification "force deleted" event.
     *
     * @return void
     */
    public function forceDeleted(AdminNotification $adminNotification)
    {
        $this->sendBroadcast([]);
    }
}
