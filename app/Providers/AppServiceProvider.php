<?php

namespace App\Providers;

use App\Extension;
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
            if(Auth::user()->hasAccess($target,$id)){
                return true;
            }else{
                return false;
            }
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
