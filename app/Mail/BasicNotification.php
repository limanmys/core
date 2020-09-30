<?php

namespace App\Mail;

use App\Models\AdminNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class BasicNotification extends Mailable
{
    use Queueable, SerializesModels;
    public $user, $notification;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(AdminNotification $notification)
    {
        $this->notification = $notification;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from([
            "address" => env('APP_NOTIFICATION_EMAIL'),
            "name" => __("Liman Bildiri Sistemi"),
        ])->view('email.external_notification');
    }
}
