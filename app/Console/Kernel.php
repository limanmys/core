<?php

namespace App\Console;

use App\AdminNotification;
use App\Notification;
use App\User;
use App\Http\Controllers\MarketController;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\DB;

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
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Delete Old Tokens every night
        $schedule
            ->call(function () {
                DB::table('tokens')->truncate();
            })
            ->dailyAt("23:59")
            ->name('Token Cleanup');

        // Run Health Check every hour.
        $schedule
            ->call(function () {
                $messages = checkHealth();
                if ($messages[0]["type"] != "success") {
                    AdminNotification::where(
                        'type',
                        'health_problem'
                    )->delete();
                    AdminNotification::create([
                        "title" => "Sağlık Problemi Bulundu!",
                        "type" => "health_problem",
                        "message" =>
                            "Detaylar için lütfen ayarlardan sağlık kontrolünü kontrol edin.",
                        "level" => 3,
                    ]);
                }
            })
            ->hourly()
            ->name('Health Check');

        //Check Package Update Every 30 Min
        $schedule
            ->call(function () {
                $controller = new MarketController();

                if (!env('MARKET_ACCESS_TOKEN')) {
                    return;
                }
                $client = $controller->getClient();
                try {
                    $response = $client->post(
                        env("MARKET_URL") . '/api/users/me'
                    );
                } catch (\Exception $e) {
                    return;
                }
                $array = $controller->checkMarketUpdates(true);
                $collection = collect($array);
                if ($collection->where("updateAvailable", 1)->count()) {
                    AdminNotification::where('type', 'liman_update')->delete();
                    AdminNotification::create([
                        "title" => "Liman Güncellemesi Mevcut!",
                        "type" => "liman_update",
                        "message" =>
                            "Yeni bir sistem güncellemesi mevcut, ayrıntılı bilgi için tıklayınız.",
                        "level" => 3,
                    ]);
                }
            })
            ->hourly()
            ->name('Update Check');
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
