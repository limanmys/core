<?php

namespace App\Classes\Authentication;

use App\Models\User;
use Illuminate\Http\JsonResponse;

class LimanAuthenticator implements AuthenticatorInterface
{
    public function authenticate($credentials, $request): JsonResponse
    {
        $user = User::where(function ($query) use ($credentials) {
                    $query->where('email', $credentials['email'])
                        ->orWhere('username', $credentials['email']);
                })->where('auth_type', 'local')
                  ->first();

        if (! $user) {
            return response()->json(['message' => 'Kullanıcı adı veya şifreniz yanlış.'], 401);
        }

        // Set user preference of session time
        auth('api')->factory()->setTTL($user->session_time);
        
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
