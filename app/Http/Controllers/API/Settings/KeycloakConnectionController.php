<?php

namespace App\Http\Controllers\API\Settings;

use App\Exceptions\JsonResponseException;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Keycloak Connection Settings Controller
 */
class KeycloakConnectionController extends Controller
{
    /**
     * Get configuration of keycloak
     *
     * @return JsonResponse
     */
    public function getConfiguration()
    {
        return response()->json([
            'active' => (bool) env('KEYCLOAK_ACTIVE', 'false'),
            'client_id' => env('KEYCLOAK_CLIENT_ID'),
            'redirect_uri' => env('KEYCLOAK_REDIRECT_URI'),
            'base_url' => env('KEYCLOAK_BASE_URL'),
            'realm' => env('KEYCLOAK_REALM'),
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
            'client_id' => 'required|string',
            'redirect_uri' => 'required|string',
            'base_url' => 'required|string',
            'realm' => 'required|string',
        ]);

        setEnv([
            'KEYCLOAK_ACTIVE' => (bool) $request->active,
            'KEYCLOAK_CLIENT_ID' => $request->client_id,
            'KEYCLOAK_REDIRECT_URI' => $request->redirect_uri,
            'KEYCLOAK_BASE_URL' => $request->base_url,
            'KEYCLOAK_REALM' => $request->realm,
        ]);

        if ($request->client_secret) {
            setEnv([
                'KEYCLOAK_CLIENT_SECRET' => $request->client_secret,
            ]);
        }

        return response()->json([
            'message' => 'Ayarlar kaydedildi.',
        ]);
    }
}
