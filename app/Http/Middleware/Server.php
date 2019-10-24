<?php

namespace App\Http\Middleware;

use App\Certificate;
use Closure;

class Server
{
    public function handle($request, Closure $next)
    {
        if(in_array(server()->control_port,knownPorts()) && !Certificate::where([
            'server_hostname' => server()->ip_address,
            'origin' => server()->control_port
        ])->exists()){
            return redirect()->back()->withErrors([
                "message" => server()->name."(".server()->ip_address.") ".__("isimli sunucu henüz onaylanmamış!")
            ]);
        }
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
