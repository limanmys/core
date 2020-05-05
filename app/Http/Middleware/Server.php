<?php

namespace App\Http\Middleware;

use App\Certificate;
use Closure;

class Server
{
    public function handle($request, Closure $next)
    {
        if (
            in_array(server()->control_port, knownPorts()) &&
            !Certificate::where([
                'server_hostname' => server()->ip_address,
                'origin' => server()->control_port,
            ])->exists()
        ) {
            $message = __(":server_name isimli sunucu henüz onaylanmamış!", [
                "server_name" =>
                    server()->name . "(" . server()->ip_address . ")",
            ]);
            abort(504, $message);
            return redirect()
                ->back()
                ->withErrors([
                    "message" => $message,
                ]);
        }
        $status = @fsockopen(
            server()->ip_address,
            server()->control_port,
            $errno,
            $errstr,
            intval(env('SERVER_CONNECTION_TIMEOUT')) / 1000
        );
        if (is_resource($status)) {
            return $next($request);
        } else {
            $message = __(":server_name isimli sunucuya erişim sağlanamadı!", [
                "server_name" =>
                    server()->name . "(" . server()->ip_address . ")",
            ]);
            abort(504, $message);
            return redirect()
                ->back()
                ->withErrors([
                    "message" => $message,
                ]);
        }
    }
}
