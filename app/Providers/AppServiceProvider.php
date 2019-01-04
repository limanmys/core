<?php

namespace App\Providers;

use App\Extension;
use App\Notification;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Auth;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $extensions = Extension::where('status', 0)->get();
        View::share('extensions', $extensions);
        Blade::if('p', function ($target,$id = null) {
            return \Auth::user()->hasAccess($target,$id);
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
