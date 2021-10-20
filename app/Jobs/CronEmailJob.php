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
    protected $users;
    protected $server;
    protected $extension;
    protected $to;

    public function __construct(CronMail $mailObj)
    {
        $this->obj = $mailObj;
        $this->users = User::find(json_decode($mailObj->user_id));
        $this->server = Server::find($mailObj->server_id);
        $this->extension = Extension::find($mailObj->extension_id);
        $this->to = json_decode($this->obj->to);
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

        foreach ($this->users as $user) {
            $output = Command::runLiman(":time: | grep @{:encoded} | grep @{:user_id}", [
                "time" => $time,
                "encoded" => $encoded,
                "user_id" => $user->id
            ]);
    
            $data = [];
            if(!empty($output)){
                foreach(explode("\n", trim($output)) as $row){
                    $fetch = explode("liman_render:", $row);
                    if(isset($fetch[1])){
                        $message = json_decode(trim($fetch[1]));
                        if($message && isset($message->data)){
                            $decoded = json_decode($message->data);
                            $decoded && $data[] = $decoded;
                        }
                    }
                } 
            }
            
            $count = Command::runLiman(":time: | grep @{:encoded} | grep @{:user_id} | wc -l", [
                "time" => $time,
                "encoded" => $encoded,
                "user_id" => $user->id
            ]);

            if ((int) $count == 0) {
                continue;
            }

            foreach ($this->to as $to) {
                $view = view('email.cron_mail', [
                    "user_name" => $user->name,
                    "subject" => "Liman MYS Bilgilendirme",
                    "result" => $count,
                    "data" => $data,
                    "before" => $before,
                    "now" => $now,
                    "server" => $this->server,
                    "extension" => $this->extension,
                    "target" => $this->getTagText($this->obj->target, $this->extension->name),
                    "from" => trim(env("APP_NOTIFICATION_EMAIL")),
                    "to" => $to
                ])->render();
                $file = "/tmp/" . str_random(16);
                file_put_contents($file, $view);
                $output = Command::runLiman("curl -s -v --connect-timeout 15 \"smtp://{:mail_host}:{:mail_port}\" -u \"{:mail_username}:{:mail_password}\" --mail-from \"{:mail_from}\" --mail-rcpt \"{:mail_receipt}\" -T {:file} 2>&1", [
                    "mail_host" => trim(env("MAIL_HOST")),
                    "mail_port" => trim(env("MAIL_PORT")),
                    "mail_username" => trim(env("MAIL_USERNAME")),
                    "mail_password" => trim(env("MAIL_PASSWORD")),
                    "mail_from" => trim(env("APP_NOTIFICATION_EMAIL")),
                    "mail_receipt" => trim($to),
                    "file" => $file
                ]);
                if (env("MAIL_DEBUG")) {
                    echo "---BEGIN---\n$output\n---END---\n";
                }
                Command::runLiman("rm @{:file}", [
                    'file' => $file
                ]);
            }
            
            $this->obj->update([
                "last" => Carbon::now()
            ]);
        }
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
