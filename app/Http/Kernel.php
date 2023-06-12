<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

/**
 * Kernel
 *
 * @extends HttpKernel
 */
class Kernel extends HttpKernel
{
    protected $middleware = [
        Middleware\XssSanitization::class,
        Middleware\CheckForMaintenanceMode::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
    ];

    protected $middlewareGroups = [
        'web' => [
            Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \App\Http\Middleware\TouchServer::class,
            \App\Http\Middleware\APILogin::class,
            \Illuminate\Session\Middleware\AuthenticateSession::class,
            \App\Http\Middleware\Language::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\ForcePasswordChange::class,
            \App\Http\Middleware\WizardChecker::class,
        ],

        'api' => ['throttle:600,1', 'bindings'],
    ];

    protected $routeMiddleware = [
        'auth' => \App\Http\Middleware\Authenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'bindings' => \Illuminate\Routing\Middleware\SubstituteBindings::class,
        'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        'parameters' => \App\Http\Middleware\Parameters::class,
        'server' => \App\Http\Middleware\Server::class,
        'server_api' => \App\Http\Middleware\ServerApi::class,
        'permissions' => \App\Http\Middleware\PermissionManager::class,
        'admin' => \App\Http\Middleware\Admin::class,
        'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        'signed' => \Illuminate\Routing\Middleware\ValidateSignature::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
        'extension' => \App\Http\Middleware\Extension::class,
        'block_except_limans' => \App\Http\Middleware\BlockExceptLimans::class,
        'google2fa' => \PragmaRX\Google2FALaravel\Middleware::class,
        'check_google_two_factor' => \App\Http\Middleware\CheckGoogleTwoFactor::class
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
