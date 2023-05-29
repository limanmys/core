<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Test Mail Template
 *
 * @extends Mailable
 */
class TestMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(public $subject, public $content)
    {
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $mail = $this->from([
            'address' => env('APP_NOTIFICATION_EMAIL'),
            'name' => __('Liman MYS'),
        ])
            ->subject($this->subject)
            ->view('email.extension_mail');

        return $mail;
    }
}
