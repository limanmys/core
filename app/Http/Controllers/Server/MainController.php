<?php

namespace App\Http\Controllers\Server;

use App\Key;
use App\Permission;
use App\Server;
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
        // Store file in /tmp directory.
        request()->file('file')->move('/tmp/',request()->file('file')->getClientOriginalName());

        // Send file to the server.
        server()->putFile('/tmp/' .request()->file('file')->getClientOriginalName(), \request('path'));

        // Build query to check if file exists in server to validate.
        $query = '[[ -f ' . request('path') .
            request()->file('file')->getClientOriginalName()  . ' ]] && echo "1" || echo "0"';
        $flag = server()->run($query);
        if($flag == "1\n"){
            return respond("Dosya başarıyla yüklendi.");
        }
        return respond('Dosya yüklenemedi.');
    }
}
