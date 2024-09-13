<?php

namespace App\Classes\Authentication;

use App\Models\User;
use Illuminate\Http\JsonResponse;

class LimanAuthenticator implements AuthenticatorInterface
{
    public function authenticate($credentials, $request): JsonResponse
    {
        $user = User::where("email", $credentials["email"])
            ->orWhere("username", $credentials["email"])
            ->first();

        if (! $user) {
            return response()->json(['message' => 'Kullanıcı adı veya şifreniz yanlış.'], 401);
        }
        
        $credentials["email"] = $user->email;

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
