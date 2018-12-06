<?php

namespace App\Providers;

use App\Extension;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;

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
        Blade::if('p_server', function () {
            if(request()->has('permissions') && is_array(request()->get('permissions')->servers) == true && count(request()->get('permissions')->servers) > 0){
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
