<?php

namespace App\Console;

use App\Jobs\CronEmailJob;
use App\Jobs\HighAvailabilitySyncer;
use App\Models\CronMail;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

/**
 * Kernel
 * Artisan console commands
 *
 * @extends ConsoleKernel
 */
class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // High availability file syncer
        $schedule
            ->call(function () {
                $job = (new HighAvailabilitySyncer())
                    ->onQueue('high_availability_syncer');
                app(Dispatcher::class)->dispatch($job);
            })
            ->name('hasync')
            ->everyFiveMinutes();

        // Run Health Check every hour.
        $schedule
            ->call(function () {
                // TODO: Health check logic here when developed
            })
            ->hourly()
            ->name('Health Check');

        // Cron Mailer
        $schedule
            ->call(function () {
                $objects = CronMail::all();
                foreach ($objects as $object) {
                    $now = Carbon::now();
                    $flag = false;
                    switch ($object->cron_type) {
                        case 'hourly':
                            $before = $now->subHour();
                            if ($before->greaterThan($object->last)) {
                                $flag = true;
                            }
                            break;
                        case 'daily':
                            $before = $now->subDay();
                            if ($before->greaterThan($object->last)) {
                                $flag = true;
                            }
                            break;
                        case 'weekly':
                            $before = $now->subWeek();
                            if ($before->greaterThan($object->last)) {
                                $flag = true;
                            }
                            break;
                        case 'monthly':
                            $before = $now->subMonth();
                            if ($before->greaterThan($object->last)) {
                                $flag = true;
                            }
                            break;
                    }
                    if ($flag) {
                        $job = (new CronEmailJob(
                            $object
                        ))->onQueue('cron_mail');
                        app(Dispatcher::class)->dispatch($job);
                    }
                }
            })
            ->everyMinute()
            ->name('Mail Check');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
