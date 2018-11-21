<?php

namespace App\Http\Middleware;

use Closure;

class Server
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
        $server_id = null;
        if($request->route('server_id') != null){
            $server_id = $request->route('server_id');
        }else if($request->has('server_id')){
            $server_id = $request->get('server_id');
        }
        if($request != null){
            //Let's verify server.
            $server = \App\Server::where('_id',$server_id)->first();
            //If server is simply not found.
            if($server == null){
                $message = __("Sunucu bulunamadı.");
                if($request->ajax()){
                    return response([
                        "message" => $message
                    ],404);
                }
                return response()->view('general.error',[
                    "message" => $message
                ]);
            }
            //Check if ssh port is active on server.
            if($server->sshPortEnabled() == false){
                $message = __("Sunucuyla bağlantı kurulamadı.");
                if($request->ajax()){
                    return response([
                        "message" => $message
                    ],503);
                }
                return response()->view('general.error',[
                    "message" => $message
                ]);
            }
            //Check if SSH key is valid or even exist for user.
            if($server->integrity() == false){
                $message = __("Sunucuya erişmek için izniniz yok.");
                if($request->ajax()){
                    return response([
                        "message" => $message
                    ],401);
                }
                return response()->view('general.error',[
                    "message" => $message
                ]);
            }
            //Now that everything is checked, add server variable to request to easy access and prevent more database queries.
            $request->request->add(['server' => $server]);
        }else{
            $message = __("Server bilgisi verilmedi.");
            if($request->ajax()){
                return response([
                    "message" => $message
                ],401);
            }
            return response()->view('general.error',[
                "message" => $message
            ]);
        }
        return $next($request);
    }
}
