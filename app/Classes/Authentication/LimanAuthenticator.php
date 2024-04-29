<?php

namespace App\Classes\Authentication;

use Illuminate\Http\JsonResponse;

class LimanAuthenticator implements AuthenticatorInterface
{
    public function authenticate($credentials, $request): JsonResponse
    {
        $token = auth('api')->attempt($credentials);
        if (! $token) {
            return response()->json(['message' => 'Kullanıcı adı veya şifreniz yanlış.'], 401);
        }

        if (auth('api')->user()->forceChange) {
            return response()->json(['message' => 'Şifrenizi değiştirmeniz gerekmektedir.'], 405);
        }

        return Authenticator::createNewToken($token, $request);
    }
}
