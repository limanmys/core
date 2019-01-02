<?php

namespace App\Http\Controllers\Server;

use App\Key;
use App\Permission;
use App\Server;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class MainController extends Controller
{
    public function index()
    {
        // Retrieve all servers.
        $servers = Server::getAll();

        return view('server.index', [
            "servers" => $servers
        ]);
    }

    public function remove()
    {
        Server::where('_id', \request('server_id'))->delete();
        Key::where('server_id', \request('server_id'))->delete();
        $user_permissions = Permission::where('server', 'like', request('server_id'))->get();
        foreach ($user_permissions as $permission) {
            $servers = $permission->server;
            unset($servers[array_search('server_id', $servers)]);
            $permission->server = $servers;
            $permission->save();
        }
        return respond(route('servers'),300);
    }

    public function upload(){
        request()->file('files')->move('/tmp/',request()->file('files')->getClientOriginalName());
        server()->putFile('/tmp/' .request()->file('files')->getClientOriginalName(), \request('path'));

        return respond("Dosya başarıyla yüklendi.");
    }
}
