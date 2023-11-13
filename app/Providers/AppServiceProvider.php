<?php

namespace App\Providers;

use App\Models\Notification;
use App\Models\Server;
use App\Observers\NotificationObserver;
use App\Observers\ServerObserver;
use App\Observers\UserObserver;
use App\User;
use Carbon\Carbon;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Pagination\Paginator;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

/**
 * App Service Provider
 *
 * @extends ServiceProvider
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(
        Router $router,
        Kernel $kernel
    )
    {
        Paginator::useBootstrap();
        Carbon::setLocale(app()->getLocale());
        Notification::observe(NotificationObserver::class);
        User::observe(UserObserver::class);
        Server::observe(ServerObserver::class);

        Relation::morphMap([
            'users' => 'App\User',
            'roles' => 'App\Models\Role',
        ]);

        if (! request()->headers->has('liman-token')) {
            $router->pushMiddlewareToGroup(
                'web',
                \App\Http\Middleware\VerifyCsrfToken::class
            );
        }

        ResetPassword::createUrlUsing(function ($user, string $token) {
            return sprintf(
                '%s/auth/reset_password?token=%s&email=%s', 
                request()->getSchemeAndHttpHost(), 
                $token, 
                $user->getEmailForPasswordReset()
            );
        });
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
