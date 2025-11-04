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
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public static function createNewToken($token, ?Request $request = null)
    {
        $id = auth('api')->user()->id;
        $user = User::find($id);

        $user->update([
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
                            $defaultPermissions["dashboard"][] = "auth_logs";
                            $defaultPermissions["dashboard"][] = "extensions";
                            return $defaultPermissions;
                        }

                        $permissions = Permission::whereIn(
                            'morph_id', 
                            auth('api')->user()->roles->pluck('id')->toArray()
                        )
                            ->where('morph_type', 'roles')
                            ->where('type', 'view')
                            ->get();

                        $viewPermissions = [
                            ...$defaultPermissions,
                        ];

                        $dashboardPermissions = [];
                        $hasDashboardPermission = false;
                        $permissions->map(function ($permission) use (&$dashboardPermissions, &$viewPermissions, &$hasDashboardPermission) {
                            if ($permission->key === "sidebar") {
                                // if sidebar is set to extensions, you cannot override it.
                                if (isset($viewPermissions["sidebar"]) && $viewPermissions["sidebar"] === "extensions") {
                                    return;
                                }
                                try {
                                    $viewPermissions["sidebar"] = json_decode($permission->value, false, 512, JSON_THROW_ON_ERROR);
                                } catch (\Exception $e) {
                                    $viewPermissions["sidebar"] = $permission->value;
                                }
                            }

                            if ($permission->key === "dashboard") {
                                $hasDashboardPermission = true;
                                // merge all dashboard permissions that comes from roles
                                $dashboardPermissions = array_unique([
                                    ...$dashboardPermissions,
                                    ...json_decode($permission->value),
                                ]);
                            }

                            if ($permission->key === "redirect") {
                                if (! auth('api')->user()->isAdmin()) {
                                    $viewPermissions["redirect"] = $permission->value;
                                }
                            }
                        });

                        // if there is no dashboard permission, set it to default
                        $viewPermissions["dashboard"] = $hasDashboardPermission
                            ? $dashboardPermissions 
                            : $defaultPermissions["dashboard"];
                        
                        return $viewPermissions;
                    })(),
                ],
            ],
        ];

        $sessionCheck = (bool) env('AUTH_SESSION_EXPIRES_ON_CLOSE', false);
        if ($sessionCheck) {
            $tokenTimeout = 0;
        } else {
            $tokenTimeout = auth('api')->factory()->getTTL() * 60;
        }

        // OIDC kullanıcıları için callback URL'den ana sayfaya redirect
        if ($user->auth_type === 'oidc' && $request && $request->has('callback_url')) {
            $callbackUrl = $request->input('callback_url');
            
            // Callback URL'yi parse et ve ana sayfaya redirect URL'i oluştur
            $parsedUrl = parse_url($callbackUrl);
            $baseUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'] . 
                      (isset($parsedUrl['port']) ? ':' . $parsedUrl['port'] : '');
            
            $redirectResponse = redirect($baseUrl . '/');
            
            // Cookie'leri redirect response'a ekle
            return $redirectResponse
                ->withCookie(cookie(
                    'token',
                    $token,
                    $tokenTimeout,
                    null,
                    $request->getHost(),
                    true,
                    true,
                    false
                ))
                ->withCookie(cookie(
                    'currentUser',
                    json_encode($return),
                    $tokenTimeout,
                    null,
                    $request->getHost(),
                    true,
                    false,
                    false
                ));
        }

        return response()->json($return)->withCookie(cookie(
            'token',
            $token,
            $tokenTimeout,
            null,
            $request->getHost(),
            true,
            true,
            false
        ))->withCookie(cookie(
            'currentUser',
            json_encode($return),
            $tokenTimeout,
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
        Log::channel('auth')->warning('LOGIN_FAILED', ['email' => $email, 'ip' => request()->ip()]);

        return response()->json(['message' => 'Kullanıcı adı veya şifreniz yanlış.'], 401);
    }
}
