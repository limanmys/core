<?php

namespace App\Jobs;

use App\Mail\CronMail as CronMailObj;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\CronMail;
use Illuminate\Support\Facades\Mail;

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

    public function __construct(CronMail $mailObj)
    {
        $this->obj = $mailObj;
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

        Mail::to($this->obj->to)->send(new CronMailObj($this->obj, $count, $before, $now));

        $this->obj->update([
           "last" => Carbon::now()
        ]);
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
