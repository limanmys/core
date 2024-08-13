<?php

namespace App\Classes\Authentication;

use App\Models\Oauth2Token;
use App\Models\User;
use GuzzleHttp\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class KeycloakAuthenticator implements AuthenticatorInterface
{
    public function authenticate($credentials, $request): JsonResponse
    {
        $client = new Client([
            'verify' => false,
        ]);

        try {
            $r = $client->post(
                env('KEYCLOAK_BASE_URL').'/realms/'.env('KEYCLOAK_REALM').'/protocol/openid-connect/token',
                [
                    'form_params' => [
                        'client_id' => env('KEYCLOAK_CLIENT_ID'),
                        'client_secret' => env('KEYCLOAK_CLIENT_SECRET'),
                        'username' => $request->email,
                        'password' => $request->password,
                        'grant_type' => 'password',
                        'scope' => 'openid',
                    ],
                ]
            );
        } catch (\Exception $e) {
            Log::error('Keycloak authentication failed. '.$e->getMessage());

            return Authenticator::returnLoginError($request->email);
        }

        $response = json_decode($r->getBody()->getContents(), true);
        if (! isset($response['access_token'])) {
            Log::error('Keycloak authentication failed. Access token is missing.');

            return Authenticator::returnLoginError($request->email);
        }
        $details = json_decode(base64_decode(str_replace('_', '/', str_replace('-', '+', explode('.', $response['access_token'])[1]))));

        $create = User::where('email', strtolower($request->email))
            ->orWhere('username', strtolower($request->email))
            ->first();

        if (! $create) {
            $user = User::create([
                'id' => $details->sub,
                'name' => $details->name,
                'email' => $details->email,
                'username' => $details->preferred_username,
                'auth_type' => 'keycloak',
                'password' => Hash::make(Str::random(16)),
                'forceChange' => false,
            ]);
        } else {
            $user = User::where('id', $details->sub)->first();
        }

        Oauth2Token::updateOrCreate([
            'user_id' => $details->sub,
            'token_type' => $response['token_type'],
        ], [
            'user_id' => $details->sub,
            'token_type' => $response['token_type'],
            'access_token' => $response['access_token'],
            'refresh_token' => $response['refresh_token'],
            'expires_in' => (int) $response['expires_in'],
            'refresh_expires_in' => (int) $response['refresh_expires_in'],
        ]);

        return Authenticator::createNewToken(
            auth('api')->login($user),
            $request
        );
    }
}
