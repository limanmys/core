<?php

namespace App\Http\Middleware;

use Closure;

class ServerApi
{
    public function handle($request, Closure $next)
    {
        $status = @fsockopen(server()->ip_address,server()->control_port,$errno,$errstr,(intval(env('SERVER_CONNECTION_TIMEOUT')) / 1000));
        if(is_resource($status)){
            return $next($request);
        }else{
            return redirect()->back()->withErrors([
                "message" => server()->name."(".server()->ip_address.") ".__("isimli sunucuya erişim sağlanamadı!")
            ]);
        }
    }
}
