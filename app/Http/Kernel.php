<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Illuminate\Http\Middleware\HandleCors;

/**
 * Kernel
 *
 * @extends HttpKernel
 */
class Kernel extends HttpKernel
{
    protected $middleware = [
        HandleCors::class,
        Middleware\XssSanitization::class,
        Middleware\CheckForMaintenanceMode::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        Middleware\TrustProxies::class,
        Middleware\EncryptCookies::class,
        Middleware\APILogin::class,
    ];

    protected $middlewareGroups = [
        'web' => [
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\Session\Middleware\AuthenticateSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\ForcePasswordChange::class,
        ],

        'api' => [
            'throttle:600,1',
            'bindings',
            Middleware\CookieJWTAuthenticator::class,
            Middleware\ClearTokenOnUnauthorized::class,
            Middleware\APILocalization::class,
        ],
    ];

    protected $middlewareAliases = [
        'auth' => \App\Http\Middleware\Authenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'bindings' => \Illuminate\Routing\Middleware\SubstituteBindings::class,
        'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        'server' => \App\Http\Middleware\Server::class,
        'permissions' => \App\Http\Middleware\PermissionManager::class,
        'admin' => \App\Http\Middleware\Admin::class,
        'signed' => \Illuminate\Routing\Middleware\ValidateSignature::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
        'extension' => \App\Http\Middleware\Extension::class,
        'block_except_limans' => \App\Http\Middleware\BlockExceptLimans::class,
        'google2fa' => \PragmaRX\Google2FALaravel\MiddlewareStateless::class,
    ];

    protected $middlewarePriority = [
        \Illuminate\Session\Middleware\StartSession::class,
        \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        \App\Http\Middleware\Authenticate::class,
        \Illuminate\Session\Middleware\AuthenticateSession::class,
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
        \Illuminate\Auth\Middleware\Authorize::class,
    ];
}
