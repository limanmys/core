<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use SocialiteProviders\Keycloak\KeycloakExtendSocialite;
use SocialiteProviders\Manager\SocialiteWasCalled;

/**
 * Event Service Provider
 *
 * @extends ServiceProvider
 */
class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class
        ],
        SocialiteWasCalled::class => [
            KeycloakExtendSocialite::class . '@handle',
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();
        registerModuleListeners();
    }

    /**
     * Laravel Event Discover Toggle
     *
     * @return bool
     */
    public function shouldDiscoverEvents(): bool
    {
        return true;
    }

    /**
     * Discover Events on this Directories
     *
     * @return string[]
     */
    protected function discoverEventsWithin()
    {
        return ['/liman/modules/'];
    }
}
