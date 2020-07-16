<?php

namespace App\Observers;

use App\Models\AdminNotification;
use App\User;
use App\Notifications\NotificationSent;

class AdminNotificationObserver
{
    private function sendBroadcast($adminNotification)
    {
        $adminUsers = User::where('status', 1)->get();
        foreach ($adminUsers as $user) {
            $user->notify(new NotificationSent($adminNotification));
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
        $this->sendBroadcast($adminNotification);
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
