<?php

namespace App\Http\Middleware;

use Closure;
use App\Http\Controllers\Extension\Sandbox\MainController;

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
            "extension_server",
            "login",
            "extension_server_settings_page",
            "extension_server_settings",
            "logout",
            "password_change",
            "password_change_save",
        ];
        if (config('liman.liman_restricted') == true && user() && !user()->isAdmin()) {
            $request->request->add([
                'server_id' => config('liman.liman_restricted_server'),
                'extension_id' => config('liman.liman_restricted_extension'),
                "server" => \App\Server::find(config('liman.liman_restricted_server')),
                "extension" => \App\Extension::find(
                    config('liman.liman_restricted_extension')
                ),
            ]);
            if (!in_array(\Request::route()->getName(), $safeRoutes)) {
                return redirect()->route("extension_server", [
                    "extension_id" => config('liman.liman_restricted_extension'),
                    "server_id" => config('liman.liman_restricted_server'),
                    "city" => server()->city,
                ]);
            }
        }
        return $next($request);
    }
}
