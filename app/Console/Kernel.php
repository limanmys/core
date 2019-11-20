<?php

namespace App\Console;

use App\AdminNotification;
use App\Notification;
use App\User;
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
        $schedule->call(function (){
            DB::table('tokens')->truncate();
        })->dailyAt("23:59")->name('Token Cleanup');

        // Run Health Check every hour.
        $schedule->call(function (){
            $messages = checkHealth();
            if($messages[0]["type"] != "success"){
                AdminNotification::where('type', 'health_problem')->delete();
                $notification = new AdminNotification();
                $notification->title = "Sağlık Problemi Bulundu!";
                $notification->type = "health_problem";
                $notification->message = "Detaylar için lütfen ayarlardan sağlık kontrolünü kontrol edin.";
                $notification->level = 3;
                $notification->save();
            }
        })->hourly()->name('Health Check');

        //Check Package Update Every 30 Min
        $schedule->call(function (){
            $output = shell_exec("sudo apt update && apt list --upgradable | grep 'liman'");
            if(!strpos($output,"liman")){
                return;
            }
            AdminNotification::where('type', 'liman_update')->delete();
            $notification = new AdminNotification();
            $notification->title = "Liman Güncellemesi Mevcut!";
            $notification->type = "liman_update";
            $notification->message = "Yeni bir liman sürümü mevcut ayrıntılı bilgi için tıklayınız.";
            $notification->level = 3;
            $notification->save();
        })->everyThirtyMinutes()->name('Update Check');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
