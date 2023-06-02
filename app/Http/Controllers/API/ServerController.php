<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Server;

class ServerController extends Controller
{
    public function index()
    {
        $servers = Server::orderBy('updated_at', 'DESC')
            ->get()
            ->filter(function ($server) {
                return Permission::can(auth('api')->user()->id, 'server', 'id', $server->id);
            });

        return response()->json($servers);
    }
}
