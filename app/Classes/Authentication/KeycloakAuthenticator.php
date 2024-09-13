<?php

namespace App\Classes\Authentication;

use App\Models\Oauth2Token;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Keycloak\KeycloakClient;
use Keycloak\User\UserApi;
use Stevenmaguire\OAuth2\Client\Provider\Keycloak as KeycloakProvider;

class KeycloakAuthenticator implements AuthenticatorInterface
{
    private $kcClient;

    private $oauthProvider;

    private $kcUserApi;

    public function __construct()
    {
        $this->kcClient = new KeycloakClient(
            env('KEYCLOAK_CLIENT_ID'),
            env('KEYCLOAK_CLIENT_SECRET'),
            env('KEYCLOAK_REALM'),
            env('KEYCLOAK_BASE_URL'),
            null,
            ''
        );

        $this->kcUserApi = new UserApi($this->kcClient);

        $this->oauthProvider = new KeycloakProvider([
            'authServerUrl'     => env('KEYCLOAK_BASE_URL'),
            'realm'             => env('KEYCLOAK_REALM'),
            'clientId'          => env('KEYCLOAK_CLIENT_ID'),
            'clientSecret'      => env('KEYCLOAK_CLIENT_SECRET'),
            'redirectUri'       => env('KEYCLOAK_REDIRECT_URI'),
            'version'           => '24.0.0',
        ]);
    }

    public function authenticate($credentials, $request): JsonResponse
    {
        try {
            $accessTokenObject = $this->oauthProvider->getAccessToken('password', [
                'username' => $request->email,
                'password' => $request->password,
                'scope'    => 'openid',
            ]);

            $resourceOwner = $this->oauthProvider->getResourceOwner($accessTokenObject);

            $roles = collect($this->kcUserApi->getRoles($resourceOwner->getId()))
                    ->map(function ($role) {
                        return $role->name;
                    })->toArray();
        } catch (\Exception $e) {
            Log::error('Keycloak authentication failed. '.$e->getMessage());

            return Authenticator::returnLoginError($request->email);
        }

        $create = User::where('email', strtolower($request->email))
            ->orWhere('username', strtolower($request->email))
            ->first();

        if (! $create) {
            $user = User::create([
                'id' => $resourceOwner->getId(),
                'name' => $resourceOwner->getName(),
                'email' => $resourceOwner->getEmail(),
                'username' => $resourceOwner->getUsername(),
                'auth_type' => 'keycloak',
                'password' => Hash::make(Str::uuid()),
                'forceChange' => false,
            ]);
        } else {
            $user = User::where('id', $resourceOwner->getId())->first();
        }

        Oauth2Token::updateOrCreate([
            'user_id' => $resourceOwner->getId(),
            'token_type' => $accessTokenObject->getValues()['token_type'],
        ], [
            'user_id' => $resourceOwner->getId(),
            'token_type' => $accessTokenObject->getValues()['token_type'],
            'access_token' => $accessTokenObject->getToken(),
            'refresh_token' => $accessTokenObject->getRefreshToken(),
            'expires_in' => $accessTokenObject->getExpires(),
            'refresh_expires_in' => $accessTokenObject->getValues()['refresh_expires_in'],
            'permissions' => $roles,
        ]);

        return Authenticator::createNewToken(
            auth('api')->login($user),
            $request
        );
    }
}
