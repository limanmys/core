<?php

namespace App\Console;

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
                $admins = User::where('status','1')->get();
                foreach ($admins as $admin){
                    $notification = new Notification();
                    $notification->user_id = $admin->id;
                    $notification->title = "Sağlık Sorunu Bulundu!";
                    $notification->type = "error";
                    $notification->message = "Detaylar için lütfen ayarlardan sağlık kontrolünü kontrol edin.";
                    $notification->level = "";
                    $notification->read = "0";
                    $notification->save();
                }
            }
        })->hourly()->name('Health Check');
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
