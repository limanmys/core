<?php

namespace App\Classes\Authentication;

use App\Classes\Authentication\OIDC\OIDCFlowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * AuthenticatorInterface ile mevcut AuthController kablolamasını koruyan ince
 * facade. Tüm OIDC mantığı {@see OIDCFlowService} ve collaborator'larına
 * taşındı.
 */
class OIDCAuthenticator implements AuthenticatorInterface
{
    public function authenticate($credentials, $request): JsonResponse
    {
        return app(OIDCFlowService::class)->initiate($request);
    }

    public static function handleCallback(Request $request): JsonResponse|RedirectResponse
    {
        return app(OIDCFlowService::class)->handleCallback($request);
    }
}
