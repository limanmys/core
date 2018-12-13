<?php

namespace App\Http\Middleware;

use Closure;

class Server
{
    public function handle($request, Closure $next)
    {
        $server_id = null;
        if ($request->route('server_id') != null) {
            $server_id = $request->route('server_id');
        } else if ($request->has('server_id')) {
            $server_id = $request->get('server_id');
        }
        if ($request != null) {
            //Let's verify server.
            $server = \App\Server::where('_id', $server_id)->first();
            //If server is simply not found.
            if ($server == null) {
                return $this->response(__("Sunucu bulunamadı."));
            }
            //Check if ssh port is active on server.
            if ($server->sshPortEnabled() == false) {
                return $this->response(__("Sunucuyla bağlantı kurulamadı."));
            }
            //Check if SSH key is valid or even exist for user.
            if ($server->integrity() == false) {
                $message = __("SSH: Sunucuya erişmek için izniniz yok.");
                if ($request->ajax()) {
                    return response([
                        "message" => $message
                    ], 401);
                }
                return redirect(route('keys'));
            }
            //Now that everything is checked, add server variable to request to easy access and prevent more database queries.
            $request->request->add(['server' => $server]);
        } else {
            return $this->response(__("Server bilgisi verilmedi."));
        }
        return $next($request);
    }

    private function response($message)
    {
        if (request()->wantsJson()) {
            return response([
                "message" => $message
            ], 401);
        }else{
            return response()->view('general.error', [
                "message" => $message
            ]);    
        }
    }
}
