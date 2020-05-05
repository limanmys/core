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
        if (env('LIMAN_RESTRICTED') == true && user() && !user()->isAdmin()) {
            $request->request->add([
                'server_id' => env("LIMAN_RESTRICTED_SERVER"),
                'extension_id' => env("LIMAN_RESTRICTED_EXTENSION"),
                "server" => \App\Server::find(env("LIMAN_RESTRICTED_SERVER")),
                "extension" => \App\Extension::find(
                    env("LIMAN_RESTRICTED_EXTENSION")
                ),
            ]);
            if (!in_array(\Request::route()->getName(), $safeRoutes)) {
                return redirect()->route("extension_server", [
                    "extension_id" => env("LIMAN_RESTRICTED_EXTENSION"),
                    "server_id" => env("LIMAN_RESTRICTED_SERVER"),
                    "city" => server()->city,
                ]);
            }
        }
        return $next($request);
    }
}
