<?php

namespace App\Providers;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

use App\Notification;
use App\AdminNotification;
use App\Observers\NotificationObserver;
use App\Observers\AdminNotificationObserver;
use Illuminate\Database\Eloquent\Relations\Relation;

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


        Relation::morphMap([
            'users' => 'App\User',
            'roles' => 'App\Role',
        ]);
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
