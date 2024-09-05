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
        $id = auth('api')->user()->id;

        User::find($id)->update([
            'last_login_at' => Carbon::now()->toDateTimeString(),
            'last_login_ip' => $request->ip(),
        ]);

        AuthLog::create([
            'user_id' => $id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $return = [
            'expired_at' => (auth('api')->factory()->getTTL() * 60 + time()) * 1000,
            'user' => [
                ...User::find($id, [
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
                    'server_details' => Permission::can($id, 'liman', 'id', 'server_details'),
                    'server_services' => Permission::can($id, 'liman', 'id', 'server_services'),
                    'add_server' => Permission::can($id, 'liman', 'id', 'add_server'),
                    'update_server' => Permission::can($id, 'liman', 'id', 'update_server'),
                    'view_logs' => Permission::can($id, 'liman', 'id', 'view_logs'),
                    'view' => (function () {
                        $defaultPermissions = config('liman.default_views');

                        if (auth('api')->user()->isAdmin()) {
                            return $defaultPermissions;
                        }

                        // TODO: Check priorities of permission values
                        // If something is different than default, it should be returned
                        $permissions = Permission::whereIn(
                            'morph_id', 
                            auth('api')->user()->roles->pluck('id')->toArray()
                        )
                            ->where('morph_type', 'roles')
                            ->where('type', 'view')
                            ->get();

                        $customPermissions = $permissions->map(function ($item) {
                            return [
                                $item->key => json_decode($item->value),
                            ];
                        })->toArray();
                        
                        $filteredPermissions = array_filter($customPermissions, function ($permission) use ($defaultPermissions) {
                            return !in_array($permission, $defaultPermissions);
                        });
                        
                        return [
                            ...$defaultPermissions,
                            ...$filteredPermissions,
                        ];
                    })(),
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
