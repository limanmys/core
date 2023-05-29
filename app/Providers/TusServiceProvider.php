<?php

namespace App\Providers;

use App\Http\Middleware\TusAuthenticated;
use Illuminate\Support\ServiceProvider;
use TusPhp\Tus\Server as TusServer;

/**
 * Tus Service Provider
 *
 * @extends ServiceProvider
 */
class TusServiceProvider extends ServiceProvider
{
    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('tus-server', function ($app) {
            \TusPhp\Config::set([
                'file' => [
                    'dir' => '/tmp/',
                    'name' => 'tus_php.cache',
                ],
            ]);

            $server = new TusServer();

            $server->middleware()->add(TusAuthenticated::class);

            $server->setApiPath('/upload');

            return $server;
        });
    }
}
