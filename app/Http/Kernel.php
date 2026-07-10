<?php

namespace App\Http;

use App\Http\Middleware\Admin;
use App\Http\Middleware\Authenticate;
use App\Http\Middleware\BlockExceptLimans;
use App\Http\Middleware\Extension;
use App\Http\Middleware\ForcePasswordChange;
use App\Http\Middleware\PermissionManager;
use App\Http\Middleware\Server;
use App\Http\Middleware\VerifyExtensionAccess;
use Illuminate\Auth\Middleware\AuthenticateWithBasicAuth;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Illuminate\Foundation\Http\Middleware\ValidatePostSize;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Http\Middleware\SetCacheHeaders;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Middleware\ValidateSignature;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use PragmaRX\Google2FALaravel\MiddlewareStateless;

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
        ValidatePostSize::class,
        Middleware\TrimStrings::class,
        ConvertEmptyStringsToNull::class,
        Middleware\TrustProxies::class,
        Middleware\EncryptCookies::class,
        Middleware\APILogin::class,
    ];

    protected $middlewareGroups = [
        'web' => [
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            AuthenticateSession::class,
            ShareErrorsFromSession::class,
            SubstituteBindings::class,
            ForcePasswordChange::class,
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
        'auth' => Authenticate::class,
        'auth.basic' => AuthenticateWithBasicAuth::class,
        'bindings' => SubstituteBindings::class,
        'cache.headers' => SetCacheHeaders::class,
        'can' => Authorize::class,
        'server' => Server::class,
        'permissions' => PermissionManager::class,
        'admin' => Admin::class,
        'signed' => ValidateSignature::class,
        'throttle' => ThrottleRequests::class,
        'verified' => EnsureEmailIsVerified::class,
        'extension' => Extension::class,
        'extension.access' => VerifyExtensionAccess::class,
        'block_except_limans' => BlockExceptLimans::class,
        'google2fa' => MiddlewareStateless::class,
    ];

    protected $middlewarePriority = [
        StartSession::class,
        ShareErrorsFromSession::class,
        Authenticate::class,
        AuthenticateSession::class,
        SubstituteBindings::class,
        Authorize::class,
    ];
}
