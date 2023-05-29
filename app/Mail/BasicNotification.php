<?php

namespace App\Mail;

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

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(public $notification)
    {
        $this->subject = __('Liman MYS Bilgilendirme');
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
        ])->view('email.external_notification');
    }
}
