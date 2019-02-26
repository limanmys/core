<?php

namespace App\Http\Middleware;

use Closure;

class Server
{
    public function handle($request, Closure $next)
    {
//        // Retrieve Server Id
//        $server_id = request('server_id');
//
//        // Check If request has server_id at all.
//        if (!$server_id) {
//            // Route specificed using server data but request doesn't have any, so abort request.
//            return respond("Server bilgisi verilmedi.", 404);
//        }
//
//        //Let's verify server.
//        $server = getObject('server',$server_id);
//
//        //If server is simply not found.
//        if ($server == null) {
//            return respond("Sunucu bulunamadı.", 404);
//        }
//
//        // Check if server is serverless, which means no validation required.
//        if ($server->serverless) {
//            return $next($request);
//        }
//
//        // Add/Update Server Object to the request.
//        $request->request->add(['server' => $server]);

        return $next($request);
    }

}
