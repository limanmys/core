<?php

namespace App\Http\Middleware;

use App\AdminNotification;
use App\Certificate;
use Closure;

class Extension
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
        // Check Extension SSL ports to validate.
        $server = server();
        $ports = explode(',',extension()->sslPorts);
        foreach ($ports as $port){
            if(Certificate::where([
                "server_hostname" => $server->ip_address,
                "origin" => trim($port)
            ])->exists()){
                continue;
            }
            $notification = new AdminNotification();
            $notification->title = "Yeni Sertifika Onayı";
            $notification->type = "cert_request";
            $notification->message = $server->ip_address . ":" . trim($port) . ":" . $server->id;
            $notification->level = 3;
            $notification->save();
            return redirect()->back()->withErrors([
                "message" => "Bu sunucu ilk defa eklendiğinden dolayı bağlantı sertifikası yönetici onayına sunulmuştur. Bu sürede bu sunucu ile eklentiye erişemezsiniz."
            ]);
        }
        return $next($request);
    }
}
