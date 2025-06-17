<?php

namespace App\Classes\Authentication;

use App\Models\Oauth2Token;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Keycloak\KeycloakClient;
use Stevenmaguire\OAuth2\Client\Provider\Keycloak as KeycloakProvider;

class KeycloakAuthenticator implements AuthenticatorInterface
{
    private $kcClient;

    private $oauthProvider;

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

            $roles = $resourceOwner->toArray()['realm_access']['roles'];

            // Put roles in Redis
            $encoded_roles = json_encode($roles);

            $isCached = Cache::put(
                sprintf("kc_roles:%s", $resourceOwner->getId()),
                $encoded_roles,
                60 * 24 * 60 * 7
            );

            if (! $isCached) {
                Log::warning('Failed to cache roles for user '.$resourceOwner->getId());
            }
        } catch (\Exception $e) {
            Log::error('Keycloak authentication failed. '.$e->getMessage());

            return Authenticator::returnLoginError($request->email);
        }

        $create = User::where('email', strtolower($request->email))
            ->orWhere('username', strtolower($request->email))
            ->first();

        if (! $create) {
            // Database transaction içinde yeni user oluştur
            $user = DB::transaction(function () use ($resourceOwner) {
                $newUser = User::create([
                    'id' => $resourceOwner->getId(),
                    'name' => $resourceOwner->getName(),
                    'email' => $resourceOwner->getEmail(),
                    'username' => $resourceOwner->getUsername(),
                    'auth_type' => 'keycloak',
                    'password' => Hash::make(Str::uuid()),
                    'forceChange' => false,
                ]);
                
                // User'ın gerçekten kaydedildiğini ve ID'sinin olduğunu kontrol et
                $newUser->refresh();
                if (!$newUser->getJWTIdentifier()) {
                    throw new \Exception('Keycloak user creation failed - JWT identifier is null');
                }
                
                return $newUser;
            });
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

        try {
            $allRealmRoles = $this->getAllRealmRoles();

            foreach ($allRealmRoles as $role) {
                if (in_array($role->name, $roles)) {
                    // If user role is matched with realm role, check attributes and assign user to liman role
                    if (isset($role->attributes->liman_role) && count($role->attributes->liman_role) > 0) {
                       // Assign all items in liman_role attribute to user
                        foreach ($role->attributes->liman_role as $limanRole) {
                            $user->assignRole($limanRole);
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to fetch realm roles from Keycloak. '.$e->getMessage());
        }

        // Set user preference of session time
        auth('api')->factory()->setTTL($user->session_time);

        return Authenticator::createNewToken(
            auth('api')->login($user),
            $request
        );
    }

    private function getAllRealmRoles()
    {
        $response = $this->kcClient->sendRequest('GET', "roles?briefRepresentation=false");

        if ($response->getStatusCode() !== 200) {
            throw new \Exception('Failed to fetch composite roles');
        }

        return json_decode($response->getBody()->getContents());
    }
}
