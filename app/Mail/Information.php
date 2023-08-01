<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Information Mail Type
 *
 * @extends Mailable
 */
class Information extends Mailable
{
    use Queueable, SerializesModels;

    public string $content;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(
        string $content
    ){
        $this->subject = __('Liman MYS Bilgilendirme');
        $this->content = $content;
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
        ])->markdown('email.information');
    }
}

