<?php

namespace App\Providers;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

use App\Notification;
use App\AdminNotification;
use App\Observers\NotificationObserver;
use App\Observers\AdminNotificationObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        View::composer('layouts.header',function($view){
            $view->with('USER_FAVORITES',user()->favorites());
        });
        Carbon::setLocale(app()->getLocale());
        Notification::observe(NotificationObserver::class);
        AdminNotification::observe(AdminNotificationObserver::class);
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
    }
}
