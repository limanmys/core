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
                return respond("Sunucu bulunamadı.",404);
            }
            //Check if ssh port is active on server.
            if (!$server->isAlive()) {
                return respond("Sunucuyla bağlantı kurulamadı.",503);
            }
            //Check if SSH key is valid or even exist for user.
            if (!$server->integrity()) {
                return respond("SSH: Sunucuya erişmek için izniniz yok.",403);
            }
            //Now that everything is checked, add server variable to request to easy access and prevent more database queries.
            $request->request->add(['server' => $server]);
        } else {
            return respond("Server bilgisi verilmedi.",404);
        }
        return $next($request);
    }

}
