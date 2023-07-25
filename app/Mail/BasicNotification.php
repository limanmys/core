<?php

namespace App\Mail;

use App\Classes\NotificationBuilder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Basic Notification Mail Type
 *
 * @extends Mailable
 */
class BasicNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $user;

    public $subject;

    public $notification;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($notification)
    {
        $this->subject = __('Liman MYS Bilgilendirme');
        $builder = new NotificationBuilder($notification);
        $this->notification = $builder->convertToBroadcastable();
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from([
            'address' => env('APP_NOTIFICATION_EMAIL'),
            'name' => __('Liman Bildiri Sistemi'),
        ])->markdown('email.external_notification');
    }
}
