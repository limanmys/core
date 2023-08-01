<?php

namespace App\Console;

use App\Jobs\HighAvailabilitySyncer;
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
