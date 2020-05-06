<?php

namespace App\Http\Middleware;

use Closure;

class ServerApi
{
    public function handle($request, Closure $next)
    {
        $status = @fsockopen(
            server()->ip_address,
            server()->control_port,
            $errno,
            $errstr,
            intval(config('liman.server_connection_timeout')) / 1000
        );
        if (is_resource($status)) {
            return $next($request);
        } else {
            return respond(
                __(":server_name isimli sunucuya erişim sağlanamadı!", [
                    "server_name" =>
                        server()->name . "(" . server()->ip_address . ")",
                ]),
                201
            );
        }
    }
}
