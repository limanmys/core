<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

use App\Models\Notification;
use App\Models\Permission;
use App\Models\AdminNotification;
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
    public function boot(
        \Illuminate\Routing\Router $router,
        \Illuminate\Contracts\Http\Kernel $kernel
    ) {
        View::composer('layouts.header', function ($view) {
            $view->with('USER_FAVORITES', user()->favorites());
            $view->with('SERVERS', \App\Models\Server::orderBy('updated_at', 'DESC')
                ->limit(env('NAV_SERVER_COUNT', 20))->get()
                ->filter(function ($server) {
                    return Permission::can(user()->id, 'server', 'id', $server->id);
                })
                ->filter(function ($server) {
                    return !(bool) user()->favorites()->where("id", $server->id)->first();
                })
            );
        });
        Carbon::setLocale(app()->getLocale());
        Notification::observe(NotificationObserver::class);
        AdminNotification::observe(AdminNotificationObserver::class);
        Relation::morphMap([
            'users' => 'App\User',
            'roles' => 'App\Models\Role',
        ]);

        if (request()->headers->has("liman-token") == false) {
            $router->pushMiddlewareToGroup(
                "web",
                \App\Http\Middleware\VerifyCsrfToken::class
            );
        }
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
