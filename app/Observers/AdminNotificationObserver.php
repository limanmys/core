<?php

namespace App\Observers;

use App\Models\AdminNotification;
use App\User;
use App\Notifications\NotificationSent;
use App\Mail\BasicNotification;
use Illuminate\Support\Facades\Mail;

class AdminNotificationObserver
{
    private function sendBroadcast($adminNotification)
    {
        if (!$adminNotification) {
            return;
        }
        $adminUsers = User::where('status', 1)->get();
        foreach ($adminUsers as $user) {
            $user->notify(new NotificationSent($adminNotification));
            if (env('MAIL_ENABLED') == true && $adminNotification->type == "external_notification") {
                Mail::to($user)->send(new BasicNotification($adminNotification));
            }
        }
    }
    /**
     * Handle the admin notification "created" event.
     *
     * @param  \App\Models\AdminNotification  $adminNotification
     * @return void
     */
    public function created(AdminNotification $adminNotification)
    {
        $this->sendBroadcast($adminNotification);
    }

    /**
     * Handle the admin notification "updated" event.
     *
     * @param  \App\Models\AdminNotification  $adminNotification
     * @return void
     */
    public function updated(AdminNotification $adminNotification)
    {
        $this->sendBroadcast([]);
    }

    /**
     * Handle the admin notification "deleted" event.
     *
     * @param  \App\Models\AdminNotification  $adminNotification
     * @return void
     */
    public function deleted(AdminNotification $adminNotification)
    {
        $this->sendBroadcast([]);
    }

    /**
     * Handle the admin notification "restored" event.
     *
     * @param  \App\Models\AdminNotification  $adminNotification
     * @return void
     */
    public function restored(AdminNotification $adminNotification)
    {
        $this->sendBroadcast([]);
    }

    /**
     * Handle the admin notification "force deleted" event.
     *
     * @param  \App\Models\AdminNotification  $adminNotification
     * @return void
     */
    public function forceDeleted(AdminNotification $adminNotification)
    {
        $this->sendBroadcast([]);
    }
}
