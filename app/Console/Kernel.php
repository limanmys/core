<?php

namespace App\Console;

use App\Http\Controllers\Market\MarketController;
use App\Jobs\CronEmailJob;
use App\Jobs\HighAvailabilitySyncer;
use App\Models\AdminNotification;
use App\Models\CronMail;
use App\Models\MonitorServer;
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
                $messages = checkHealth();
                if ($messages[0]['type'] != 'success') {
                    AdminNotification::where(
                        'type',
                        'health_problem'
                    )->delete();
                    AdminNotification::create([
                        'title' => json_encode([
                            'tr' => __('Sağlık Problemi Bulundu!', [], 'tr'),
                            'en' => __('Sağlık Problemi Bulundu!', [], 'en'),
                        ]),
                        'type' => 'health_problem',
                        'message' => json_encode([
                            'tr' => __('Detaylar için lütfen ayarlardan sağlık kontrolünü kontrol edin.', [], 'tr'),
                            'en' => __('Detaylar için lütfen ayarlardan sağlık kontrolünü kontrol edin.', [], 'en'),
                        ]),
                        'level' => 3,
                    ]);
                }
            })
            ->hourly()
            ->name('Health Check');

        //Check Package Update Every 30 Min
        $schedule
            ->call(function () {
                $controller = new MarketController();

                if (! env('MARKET_ACCESS_TOKEN')) {
                    return;
                }
                $client = $controller->getClient();
                try {
                    $response = $client->post(
                        env('MARKET_URL') . '/api/users/me'
                    );
                } catch (\Exception) {
                    return;
                }
                $array = $controller->checkMarketUpdates(true);
                $collection = collect($array);
                if ($collection->where('updateAvailable', 1)->count()) {
                    AdminNotification::where('type', 'liman_update')->delete();
                    AdminNotification::create([
                        'title' => __('Liman Güncellemesi Mevcut!'),
                        'type' => 'liman_update',
                        'message' => __('Yeni bir sistem güncellemesi mevcut, ayrıntılı bilgi için tıklayınız.'),
                        'level' => 3,
                    ]);
                }
            })
            ->hourly()
            ->name('Update Check');

        // Mail System.
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

        // Server monitoring
        $schedule
            ->call(function () {
                $servers = MonitorServer::all();
                foreach ($servers as $server) {
                    $online = checkPort($server->ip_address, $server->port);
                    $server->update([
                        'online' => $online,
                        'last_checked' => Carbon::now(),
                    ]);
                }
            })
            ->everyMinute()
            ->name('Server Monitoring');
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
