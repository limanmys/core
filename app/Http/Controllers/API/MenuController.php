<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Server;

class MenuController extends Controller
{
    public function servers()
    {
        $servers = Server::orderBy('updated_at', 'DESC')
            ->limit(20)
            ->get()
            ->filter(function ($server) {
                return Permission::can(user()->id, 'server', 'id', $server->id);
            })
            ->filter(function ($server) {
                return ! (bool) user()->favorites()->where('id', $server->id)->first();
            });

        return response()->json([...user()->favorites()->map(function ($server) {
            $server->is_favorite = true;

            return $server;
        }), ...$servers->map(function ($server) {
            $server->is_favorite = false;

            return $server;
        })]);
    }

    public function serverDetails(Server $server)
    {
        if (! Permission::can(user()->id, 'server', 'id', $server->id)) {
            return response()->json(['error' => 'You do not have permission to access this server.'], 403);
        }

        $server->is_online = $server->isOnline();
        $server->extensions = $server->extensions()->map(function ($extension) {
            $db = getExtensionJson($extension->name);
            if (isset($db['menus']) && $db['menus']) {
                $extension->menus = $db['menus'];
            } else {
                $extension->menus = [];
            }

            return $extension;
        });
        $server->is_favorite = (bool) user()->myFavorites()->where('server_id', $server->id)->exists();

        return response()->json($server);
    }
}