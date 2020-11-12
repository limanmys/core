<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;
use App\Models\CronMail;
use App\Jobs\CronEmailJob;
use Illuminate\Contracts\Bus\Dispatcher;

class CronEmailCheckJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {

    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $objects = CronMail::all();
        foreach($objects as $object){
            $now = Carbon::now();
            $flag = false;
            switch ($object->cron_type){
                case "hourly":
                    $before = $now->subHour();
                    if($before->greaterThan($object->last)){
                        $flag = true;
                    }
                    break;
                case "daily":
                    $before = $now->subDay();
                    if($before->greaterThan($object->last)){
                        $flag = true;
                    }
                    break;
                case "weekly":
                    $before = $now->subWeek();
                    if($before->greaterThan($object->last)){
                        $flag = true;
                    }
                    break;
                case "monthly":
                    $before = $now->subMonth();
                    if($before->greaterThan($object->last)){
                        $flag = true;
                    }
                    break;
            }
            if($flag){
                $job = (new CronEmailJob(
                    $object
                ))->onQueue('cron_mail');
                app(Dispatcher::class)->dispatch($job);
            }
        }
    }
}
