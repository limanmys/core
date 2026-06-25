<?php

namespace App\Classes\Authentication\OIDC;

use App\Models\Oauth2Token;
use App\Models\User;

/**
 * OIDC token yanıtını (access/refresh token vb.) Oauth2Token tablosunda saklar.
 *
 * Sağlayıcı "external_token" claim'i döndürmüşse (Liman'a özel custom claim)
 * access token yerine onu saklar.
 */
class OIDCTokenStore
{
    /**
     * @param  object  $tokenResponse  Token endpoint raw yanıtı (stdClass).
     * @param  array<string, mixed>  $permissions
     */
    public function persist(User $user, object $tokenResponse, ?string $externalToken, array $permissions): void
    {
        if ($externalToken) {
            Oauth2Token::updateOrCreate([
                'user_id' => $user->id,
                'token_type' => 'EXTERNAL_TOKEN',
            ], [
                'user_id' => $user->id,
                'token_type' => 'EXTERNAL_TOKEN',
                'access_token' => $externalToken,
                'refresh_token' => '',
                'expires_in' => 0,
                'refresh_expires_in' => 0,
                'permissions' => $permissions,
            ]);

            return;
        }

        Oauth2Token::updateOrCreate([
            'user_id' => $user->id,
            'token_type' => $tokenResponse->token_type ?? 'Bearer',
        ], [
            'user_id' => $user->id,
            'token_type' => $tokenResponse->token_type ?? 'Bearer',
            'access_token' => $tokenResponse->access_token ?? '',
            'refresh_token' => $tokenResponse->refresh_token ?? '',
            'expires_in' => $tokenResponse->expires_in ?? 0,
            'refresh_expires_in' => $tokenResponse->refresh_expires_in ?? 0,
            'permissions' => $permissions,
        ]);
    }
}
