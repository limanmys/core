<?php

namespace App\Providers;

use App\Classes\Authentication\OIDC\OIDCFlowService;
use App\Classes\Authentication\OIDC\OIDCRoleMapper;
use App\Classes\Authentication\OIDC\OIDCTokenStore;
use App\Classes\Authentication\OIDC\OIDCUserProvisioner;
use App\Classes\Authentication\OIDC\OpenIDConnectClient;
use Illuminate\Support\ServiceProvider;

/**
 * OIDC servislerini container'a kaydeder.
 *
 * OpenIDConnectClient constructor'ında env'den provider yapılandırması
 * (issuer, endpoints, client credentials) okunup Jumbojett'in provider
 * config'i kurulur. Bu maliyetli olduğundan ve istek boyunca sabit
 * kaldığından singleton bağlanır. Akış tek bir HTTP isteği içinde
 * ya initiate ya callback içerir; birikmiş durum (idToken, accessToken)
 * istek sonunda zaten kaybolur.
 */
class OIDCServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(OpenIDConnectClient::class);

        $this->app->singleton(OIDCUserProvisioner::class);
        $this->app->singleton(OIDCRoleMapper::class);
        $this->app->singleton(OIDCTokenStore::class);

        $this->app->singleton(OIDCFlowService::class, function ($app) {
            return new OIDCFlowService(
                $app->make(OpenIDConnectClient::class),
                $app->make(OIDCUserProvisioner::class),
                $app->make(OIDCRoleMapper::class),
                $app->make(OIDCTokenStore::class),
            );
        });
    }
}
