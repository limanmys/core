<?php

namespace App\Mail;

use App\Models\Extension;
use App\Models\Server;
use App\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Cron Mail
 *
 * @extends Mailable
 */
class CronMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    protected $obj;

    protected $user;

    protected $server;

    protected $extension;

    /**
     * Construct cron mail template
     *
     * @param $obj
     * @param $result
     * @param $before
     * @param $now
     */
    public function __construct($obj, protected $result, protected $before, protected $now)
    {
        $this->user = User::find($obj->user_id);
        $this->server = Server::find($obj->server_id);
        $this->extension = Extension::find($obj->extension_id);
        $this->obj = $obj;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $subject = $this->user->name . ' kullanıcısının ' . __($this->obj->cron_type) . ' Liman MYS Raporu';

        return $this->subject($subject)->from(env('APP_NOTIFICATION_EMAIL'))->view('email.cron_mail', [
            'user' => $this->user,
            'subject' => $subject,
            'result' => $this->result,
            'before' => $this->before,
            'now' => $this->now,
            'server' => $this->server,
            'extension' => $this->extension,
            'target' => $this->obj->target,
        ]);
    }
}
