<?php

namespace App\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\CronMail;
use App\Models\Server;
use App\Models\Extension;
use App\System\Command;
use App\User;

class CronEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @param $to
     * @param $mail
     */

    protected $obj;
    protected $user;
    protected $server;
    protected $extension;

    public function __construct(CronMail $mailObj)
    {
        $this->obj = $mailObj;
        $this->user = User::find($mailObj->user_id);
        $this->server = Server::find($mailObj->server_id);
        $this->extension = Extension::find($mailObj->extension_id);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if (!$this->doubleCheckTime()) {
            return;
        }
        if ($this->obj->type == "extension") {
            $filePath = env("LOG_EXTENSION_PATH");
        } else {
            $filePath = env("LOG_PATH");
        }
        if (!is_file($filePath)) {
            return;
        }
        $now = Carbon::now();
        switch ($this->obj->cron_type) {
            case "hourly":
                $before = Carbon::now()->subHour();
                break;
            case "daily":
                $before = Carbon::now()->subDay();
                break;
            case "weekly":
                $before = Carbon::now()->subWeek();
                break;
            case "monthly":
                $before = Carbon::now()->subMonth();
                break;
        }

        $encoded = base64_encode($this->obj->extension_id . "-" . $this->obj->server_id . "-" . $this->obj->target);
        $time = "awk -F'[]]|[[]'   '$0 ~ /^\[/ && $2 >= \"$before\" { p=1 } $0 ~ /^\[/ && $2 >= \"$now\" { p=0 } p { print $0 }' /liman/logs/extension.log";
        $command = "$time | grep '" . $encoded . "' | grep '" . $this->obj->user_id . "' | wc -l";
        $count = trim(shell_exec($command));
        $subject = $this->user->name . " kullanıcısının " . __($this->obj->cron_type) . " Liman MYS Raporu";
        $view = view('email.cron_mail', [
            "user" => $this->user,
            "subject" => $subject,
            "result" => $count,
            "before" => $before,
            "now" => $now,
            "server" => $this->server,
            "extension" => $this->extension,
            "target" => $this->getTagText($this->obj->target, $this->extension->name),
            "from" => trim(env("APP_NOTIFICATION_EMAIL")),
            "to" => $this->obj->to
        ])->render();
        $file = "/tmp/" . str_random(16);
        file_put_contents($file, $view);
        $command = "curl -s -v --connect-timeout 15 \"smtp://" . trim(env("MAIL_HOST")) . ":" . trim(env("MAIL_PORT")) . "\" -u \"" .
             trim(env("MAIL_USERNAME")) . ":" . trim(env("MAIL_PASSWORD")) . "\" --mail-from \"" . trim(env("APP_NOTIFICATION_EMAIL")) . "\" --mail-rcpt \"" .
              $this->obj->to . "\" -T " . $file . " 2>&1";
        $output = shell_exec($command);
        if (env("MAIL_DEBUG")) {
            echo "---BEGIN---\n$command\n$output\n---END---\n";
        }
        $this->obj->update([
           "last" => Carbon::now()
        ]);
        Command::runLiman("rm @{:file}", [
            'file' => $file
        ]);
    }

    private $tagTexts = [];

    private function getTagText($key, $extension_name)
    {
        if (!array_key_exists($extension_name, $this->tagTexts)) {
            $file = file_get_contents("/liman/extensions/" . strtolower($extension_name) . "/db.json");
            $json = json_decode($file, true);
            if (json_last_error() != JSON_ERROR_NONE) {
                return $key;
            }
            $this->tagTexts[$extension_name] = $json;
        }

        if (!array_key_exists("mail_tags", $this->tagTexts[$extension_name])) {
            return $key;
        }
        foreach ($this->tagTexts[$extension_name]["mail_tags"] as $obj) {
            if ($obj["tag"] == $key) {
                return $obj["description"];
            }
        }
        return $key;
    }

    public function doubleCheckTime()
    {
        $now = Carbon::now();
        $flag = false;
        switch ($this->obj->cron_type) {
                case "hourly":
                    $before = $now->subHour();
                    if ($before->greaterThan($this->obj->last)) {
                        $flag = true;
                    }
                    break;
                case "daily":
                    $before = $now->subDay();
                    if ($before->greaterThan($this->obj->last)) {
                        $flag = true;
                    }
                    break;
                case "weekly":
                    $before = $now->subWeek();
                    if ($before->greaterThan($this->obj->last)) {
                        $flag = true;
                    }
                    break;
                case "monthly":
                    $before = $now->subMonth();
                    if ($before->greaterThan($this->obj->last)) {
                        $flag = true;
                    }
                    break;
        }
        return $flag;
    }
}
