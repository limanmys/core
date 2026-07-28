<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

/**
 * Route Service Provider
 *
 * @extends ServiceProvider
 */
class RouteServiceProvider extends ServiceProvider
{
    /**
     * This namespace is applied to your controller routes.
     *
     * In addition, it is set as the URL generator's root namespace.
     *
     * @var string
     */
    protected $namespace = 'App\Http\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        RateLimiter::for('login', function ($request) {
            if ($request->type === 'oidc' && $request->has('handoff')) {
                // The BFF is the network caller, so all of its users share one
                // source IP. Do not include the untrusted client_id in the key:
                // attackers could rotate it to create unlimited rate buckets.
                return Limit::perMinute(300)->by('handoff|'.$request->ip());
            }

            return Limit::perMinute(3)->by($request->email.$request->ip());
        });

        RateLimiter::for('forgot-password', function ($request) {
            return Limit::perMinutes(5, 2)->by($request->email.$request->ip());
        });

        RateLimiter::for('upload', function ($request) {
            return Limit::perMinute(60)->by($request->user('api')?->id ?: $request->ip());
        });

        RateLimiter::for('external-notifications', function ($request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        RateLimiter::for('auth-handoff-exchange', function ($request) {
            return Limit::perMinute(300)->by($request->ip());
        });

        parent::boot();

        Route::middleware([])
            ->group(base_path('routes/health.php'));
    }

    /**
     * Define the routes for the application.
     *
     * @return void
     */
    public function map()
    {
        $this->mapApiRoutes();

        $this->mapWebRoutes();
    }

    /**
     * Define the "api" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapApiRoutes()
    {
        Route::prefix('api')
            ->middleware('api')
            ->namespace($this->namespace)
            ->group(base_path('routes/api.php'));
    }

    /**
     * Define the "web" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     *
     * @return void
     */
    protected function mapWebRoutes()
    {
        Route::middleware('web')
            ->namespace($this->namespace)
            ->group(base_path('routes/web.php'));
    }
}
