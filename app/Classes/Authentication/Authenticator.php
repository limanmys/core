<?php

namespace App\Classes\Authentication;

use App\Models\AuthLog;
use App\Models\Permission;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class Authenticator
{
    /**
     * Get the token array structure.
     *
     * @param  string  $token
     * @return \Illuminate\Http\JsonResponse
     */
    public static function createNewToken($token, ?Request $request = null)
    {
        User::find(auth('api')->user()->id)->update([
            'last_login_at' => Carbon::now()->toDateTimeString(),
            'last_login_ip' => $request->ip(),
        ]);

        AuthLog::create([
            'user_id' => auth('api')->user()->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $return = [
            'expired_at' => (auth('api')->factory()->getTTL() * 60 + time()) * 1000,
            'user' => [
                ...User::find(auth('api')->user()->id, [
                    'id',
                    'name',
                    'email',
                    'locale',
                    'status',
                    'username',
                ])->toArray(),
                'last_login_at' => Carbon::now()->toDateTimeString(),
                'last_login_ip' => $request->ip(),
                'permissions' => [
                    'server_details' => Permission::can(auth('api')->user()->id, 'liman', 'id', 'server_details'),
                    'server_services' => Permission::can(auth('api')->user()->id, 'liman', 'id', 'server_services'),
                    'add_server' => Permission::can(auth('api')->user()->id, 'liman', 'id', 'add_server'),
                    'update_server' => Permission::can(auth('api')->user()->id, 'liman', 'id', 'update_server'),
                    'view_logs' => Permission::can(auth('api')->user()->id, 'liman', 'id', 'view_logs'),
                ],
            ],
        ];

        return response()->json($return)->withCookie(cookie(
            'token',
            $token,
            auth('api')->factory()->getTTL() * 60,
            null,
            $request->getHost(),
            true,
            true,
            false
        ))->withCookie(cookie(
            'currentUser',
            json_encode($return),
            auth('api')->factory()->getTTL() * 60,
            null,
            $request->getHost(),
            true,
            false,
            false
        ));
    }

    /**
     * Return login error
     *
     * @param  string  $email
     * @return JsonResponse
     */
    public static function returnLoginError($email = '')
    {
        Log::info('Login attempt failed. '.$email);

        return response()->json(['message' => 'Kullanıcı adı veya şifreniz yanlış.'], 401);
    }
}
