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

    public $subject;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(public $notification, public $user)
    {
        $this->subject = __('Liman MYS Bilgilendirme');
        $builder = new NotificationBuilder($this->notification, $this->user->locale ?? env('APP_LOCALE', 'tr'));
        $this->notification = $builder->convertToBroadcastable();
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        // Set session locale
        app()->setLocale($this->user->locale ?? env('APP_LOCALE', 'tr'));

        return $this->from([
            'address' => env('APP_NOTIFICATION_EMAIL'),
            'name' => __('Liman Bildiri Sistemi'),
        ])->markdown('email.external_notification');
    }
}
