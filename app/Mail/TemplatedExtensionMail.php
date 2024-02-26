<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Templated Extension Mail Type
 *
 * @extends Mailable
 */
class TemplatedExtensionMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(
        public $subject, public $content, public array $attachs = []
    ){}

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $mail = $this->from([
            'address' => env('APP_NOTIFICATION_EMAIL'),
            'name' => __('Liman Bildiri Sistemi'),
        ])
            ->subject($this->subject)
            ->markdown('email.information');
        foreach ($this->attachs as $attachment) {
            $mail->attach($attachment);
        }

        return $mail;
    }
}

