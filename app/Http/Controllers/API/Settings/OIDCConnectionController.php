<?php

namespace App\Http\Controllers\API\Settings;

use App\Exceptions\JsonResponseException;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * OIDC Connection Settings Controller
 */
class OIDCConnectionController extends Controller
{
    /**
     * Get configuration of OIDC
     *
     * @return JsonResponse
     */
    public function getConfiguration()
    {
        return response()->json([
            'active' => (bool) env('OIDC_ACTIVE', 'false'),
            'issuer_url' => env('OIDC_ISSUER_URL'),
            'client_id' => env('OIDC_CLIENT_ID'),
            'redirect_uri' => env('OIDC_REDIRECT_URI'),
            'auth_endpoint' => env('OIDC_AUTH_ENDPOINT'),
            'userinfo_endpoint' => env('OIDC_USERINFO_ENDPOINT'),
            'token_endpoint' => env('OIDC_TOKEN_ENDPOINT'),
        ]);
    }

    /**
     * Save existing configuration
     *
     * @param Request $request
     * @return JsonResponse
     * @throws JsonResponseException
     */
    public function saveConfiguration(Request $request)
    {
        validate([
            'issuer_url' => 'required|string',
            'client_id' => 'required|string',
            'redirect_uri' => 'required|string',
            'auth_endpoint' => 'required|string',
            'userinfo_endpoint' => 'required|string',
            'token_endpoint' => 'required|string',
        ]);

        setEnv([
            'OIDC_ACTIVE' => (bool) $request->active,
            'OIDC_ISSUER_URL' => $request->issuer_url,
            'OIDC_CLIENT_ID' => $request->client_id,
            'OIDC_REDIRECT_URI' => $request->redirect_uri,
            'OIDC_AUTH_ENDPOINT' => $request->auth_endpoint,
            'OIDC_USERINFO_ENDPOINT' => $request->userinfo_endpoint,
            'OIDC_TOKEN_ENDPOINT' => $request->token_endpoint,
        ]);

        if ($request->client_secret) {
            setEnv([
                'OIDC_CLIENT_SECRET' => $request->client_secret,
            ]);
        }

        return response()->json([
            'message' => 'Ayarlar kaydedildi.',
        ]);
    }
}
