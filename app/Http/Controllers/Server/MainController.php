<?php

namespace App\Http\Controllers\Server;

use App\Key;
use App\Permission;
use App\Server;
use App\Http\Controllers\Controller;
use App\User;
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
        if(request('server')->user_id != Auth::id() && Auth::user()->isAdmin() == false){
            return respond("Yalnızca kendi sunucunuzu silebilirsiniz.",202);
        }
        if(request('server')->type == "linux_ssh"){
            request('server')->key->delete();
        }
        request('server')->delete();
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

    public function download(){
        // Generate random file name
        $file = str_random('10');
        server()->getFile(request('path'),'/tmp/' . $file);

        $file_name = explode("/",request('path'));
        return response()->download('/tmp/' . $file, $file_name[count($file_name) -1 ])->deleteFileAfterSend();
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

    public function grant(){
        $user = User::where('email',request('email'))->first();
        if($user == null){
            return respond("Kullanıcı bulunamadı.",404);
        }
        // Give User a permission to use this server.

        $permissions = Permission::where('user_id',$user->_id)->first();
        $user_servers = (Array) $permissions->server;
        array_push($user_servers, request('server')->_id);
        $permissions->server = $user_servers;

        // Lastly, save all information.
        $permissions->save();

        if(request('server')->type == "linux_ssh"){
            // Generate key for user.
            Key::initWithKey(request('server')->key->username, request('server')->key->_id, request('server')->ip_address,
                request('server')->port, Auth::id(), $user->_id);

            // Built key object for user.

            $key = new Key([
                "name" => request('server')->key->name,
                "username" => request('server')->key->username,
                "server_id" => request('server')->_id
            ]);

            $key->user_id = $user->_id;

            $key->save();
        }

        return respond("Yetki başarıyla verildi.");
    }

    public function revoke(){

    }
}
