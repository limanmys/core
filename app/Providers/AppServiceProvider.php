<?php

namespace App\Providers;

use App\Extension;
use App\Notification;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Events\Dispatcher;
use JeroenNoten\LaravelAdminLte\Events\BuildingMenu;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(Dispatcher $events)
    {
        // View::share('extensions', $extensions);
        // Blade::if('p', function ($target,$id = null) {
        //     return \Auth::user()->hasAccess($target,$id);
        // });
        $events->listen(BuildingMenu::class, function (BuildingMenu $event) {
            $extensions = Extension::where('status', 0)->get();
            $event->menu->add('Eklentiler');
            foreach($extensions as $extension){
                $event->menu->add([
                    'text' => $extension->name,
                    'url' => '/l/' . $extension->_id,
                    'icon' => $extension->icon
                ]);
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
