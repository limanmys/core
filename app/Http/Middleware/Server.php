<?php

namespace App\Http\Middleware;

use Closure;

class Server
{
    public function handle($request, Closure $next)
    {
        if(shell_exec("echo quit | timeout " . (intval(env('SERVER_CONNECTION_TIMEOUT')) / 1000). " telnet "
            . server()->ip_address . " " . server()->control_port . "  | grep \"Connected\"")){
            return $next($request);
        }else{
            return redirect()->back()->withErrors([
                "message" => server()->name."(".server()->ip_address.") ".__("isimli sunucuya erişim sağlanamadı!")
            ]);
        }
    }
}
