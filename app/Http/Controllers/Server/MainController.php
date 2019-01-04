<?php

namespace App\Http\Controllers\Server;

use App\Key;
use App\Permission;
use App\Server;
use App\Http\Controllers\Controller;
use Auth;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

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

    public function terminal(){
        $server = request('server');
        $client = new Client([
            'verify' => false,
            'cookies' => true
        ]);

        //First, request page to get _xsrf value.
        $response = $client->request('GET','https://localhost:4433');
        preg_match_all('/(?<=<input type=\"hidden\" name=\"_xsrf\" value=\")(.*)(?=\")/', $response->getBody()->getContents(), $output_array);
        $response = $client->request('POST','https://localhost:4433',[
            "multipart" => [
                [
                    "name" => "hostname",
                    "contents" => $server->ip_address,
                ],
                [
                    "name" => "port",
                    "contents" => "$server->port"
                ],
                [
                    "name" => "username",
                    "contents" => $server->key->username
                ],
                [
                    "name" => "_xsrf",
                    "contents" => $output_array[0][0]
                ],
                [
                    "name" => "privatekey",
                    "contents" => fopen(storage_path('keys') .
                        DIRECTORY_SEPARATOR . Auth::id(),'r')
                ]
            ],
        ]);
        $json = json_decode($response->getBody()->getContents());
        return response()->view('terminal.index',[
            "id" => $json->id
        ])->withCookie(cookie('_xsrf',$client->getConfig('cookies')->toArray()[0]["Value"]));
    }
}
