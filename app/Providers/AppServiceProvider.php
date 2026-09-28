<?php

namespace App\Providers;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Notification;
use App\Models\Server;
use App\Models\User;
use App\Observers\NotificationObserver;
use App\Observers\ServerObserver;
use App\Observers\UserObserver;
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
    ) {
        Paginator::useBootstrap();
        Carbon::setLocale(app()->getLocale());
        Notification::observe(NotificationObserver::class);
        User::observe(UserObserver::class);
        Server::observe(ServerObserver::class);

        Relation::morphMap([
            'users' => 'App\Models\User',
            'roles' => 'App\Models\Role',
        ]);

        if (! request()->headers->has('liman-token')) {
            $router->pushMiddlewareToGroup(
                'web',
                VerifyCsrfToken::class
            );
        }

        ResetPassword::createUrlUsing(function ($user, string $token) {
            return rtrim(config('app.url'), '/').'/auth/reset_password?'.http_build_query(
                ['token' => $token, 'email' => $user->getEmailForPasswordReset()],
                '',
                '&',
                PHP_QUERY_RFC3986
            );
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register() {}
}
