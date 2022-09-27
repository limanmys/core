<?php

namespace App\Http\Middleware;

use Closure;

class RestrictedMode
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $safeRoutes = [
            'extension_server',
            'login',
            'extension_server_settings_page',
            'extension_server_settings',
            'logout',
            'password_change',
            'password_change_save',
            'set_locale',
            'notifications_read',
            'user_notifications',
        ];
        if (env('LIMAN_RESTRICTED') == true && user() && ! user()->isAdmin()) {
            $request->request->add([
                'server_id' => env('LIMAN_RESTRICTED_SERVER'),
                'extension_id' => env('LIMAN_RESTRICTED_EXTENSION'),
                'server' => \App\Models\Server::find(
                    env('LIMAN_RESTRICTED_SERVER')
                ),
                'extension' => \App\Models\Extension::find(
                    env('LIMAN_RESTRICTED_EXTENSION')
                ),
            ]);
            if (
                ! in_array(\Request::route()->getName(), $safeRoutes) &&
                substr(\Request::route()->uri(), 0, 11) != 'lmn/private'
            ) {
                return redirect()->route('extension_server', [
                    'extension_id' => env('LIMAN_RESTRICTED_EXTENSION'),
                    'server_id' => env('LIMAN_RESTRICTED_SERVER'),
                    'city' => server()->city,
                ]);
            }
        }

        return $next($request);
    }
}
