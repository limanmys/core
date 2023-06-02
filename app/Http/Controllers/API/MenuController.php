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
            ->get()
            ->filter(function ($server) {
                return Permission::can(user()->id, 'server', 'id', $server->id);
            })
            ->filter(function ($server) {
                return !(bool) user()->favorites()->where('id', $server->id)->first();
            });

        return response()->json([...user()->favorites()->map(function ($server) {
            $server->is_favorite = true;
            $server->extension_count = $server->extensions()->count();
            return $server;
        }), ...$servers->map(function ($server) {
            $server->is_favorite = false;
            $server->extension_count = $server->extensions()->count();
            return $server;
        })]);
    }

    public function serverDetails(Server $server)
    {
        if (!Permission::can(user()->id, 'server', 'id', $server->id)) {
            return response()->json(['error' => 'You do not have permission to access this server.'], 403);
        }

        $server->is_online = $server->isOnline();
        $server->extensions = $server->extensions()->map(function ($extension) {
            $db = getExtensionJson($extension->name);
            if (isset($db["menus"]) && $db["menus"]) {
                $extension->menus = $db["menus"];
            } else {
                $extension->menus = [];
            }
            return $extension;
        });

        return response()->json($server);
    }
}
