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

        // Clean log table excess records every day
        $schedule
            ->call(function () {
                // This methods does keeps newest 10000 records and deletes other ones
                \App\Models\AuthLog::orderBy('created_at', 'desc')
                    ->skip(10000)
                    ->get()
                    ->each->delete();
                \App\Models\AuditLog::orderBy('created_at', 'desc')
                    ->skip(10000)
                    ->get()
                    ->each->delete();
            })
            ->daily()
            ->name('Clean Log Tables');

        // Clear expired password reset tokens every 60 minutes
        $schedule
            ->command('auth:clear-resets')
            ->hourly()
            ->name('Clear Expired Password Reset Tokens');
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
