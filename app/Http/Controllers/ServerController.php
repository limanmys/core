<?php

namespace App\Http\Controllers;

use App\Extension;
use App\Key;
use App\Script;
use App\Permission;
use App\Server;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ServerController extends Controller
{
    public static $protected = true;
    
    public function index(){
        $permissions = request('permissions');
        $servers = [];
        if($permissions->__get("server") == null){
            return view('server.index');
        }
        foreach ($permissions->server as $server_id) {
            if(Server::where('_id',$server_id)->exists()){
                array_push($servers,Server::where('_id',$server_id)->first());    
            }
        }
        return view('server.index',[
            "servers" => $servers
        ]);
    }

    public function add(Request $request){
        $data = $request->all();
        $server = new Server($data);
        $server->user_id = Auth::id();
        $server->extensions = [];
        $server->save();
        $output = Key::init(request('username'), request('password'), request('ip_address'),
            request('port'),Auth::id());
        $key = new Key($data);
        $key->server_id = $server->id;
        $key->user_id = Auth::id();
        $permissions = $request->get('permissions');
        $user_servers = $permissions->server;
        array_push($user_servers,$server->_id);
        $permissions->server = $user_servers;
        $permissions->save();
        $key->save();
        return response(route('server_one',$server->id),200);
    }

    public function remove(){
        Server::where('_id',\request('server_id'))->delete();
        Key::where('server_id', \request('server_id'))->delete();
        $user_permissions = Permission::where('server','like',request('server_id'))->get();
        foreach ($user_permissions as $permission) {
            $servers = $permission->server;
            unset($servers[array_search('server_id',$servers)]);
            $permission->server = $servers;
            $permission->save();    
        }
        return route('servers');
    }

    public function one(){
        $scripts = Script::where('extensions','server')->get();
        $server = \request('server');
        $services = $server->extensions;
        for ($i = 0 ; $i < count($services); $i++){
            if($services[$i] == "kullanıcılar" || $services[$i] == "gruplar"){
                unset($services[$i]);
                array_push($services,'ldap');
            }
        }
        return view('server.one',[
            "stats" => \request('server')->run("df -h"),
            "hostname" => request('server')->run("hostname"),
            "server" => \request('server'),
            "services" => $services,
            "scripts" => $scripts
        ]);
    }

    public function run(){
        $output = Server::where('_id',\request('server_id'))->first()->run(\request('command'));
        return $output;
    }

    public function runScript(){
        $script = Script::where('_id',\request('script_id'))->first();
        $inputs = explode(',',$script->inputs);
        $params = "";
        foreach ($inputs as $input){
            $params = $params. " " . \request(explode(':',$input)[0]);
        }
        $output = Server::where('_id',\request('server_id'))->first()->runScript($script, $params);
        return [
            "result" => 200,
            "data" => $output
        ];
    }

    public function check(){
        $extension = Extension::where('name','like',request('extension'))->first();
        $output = Server::where('_id',\request('server_id'))->first()->isRunning($extension->service);
        if($output == "active\n"){
            $result = 200;
        }else if($output === "inactive\n"){
            $result = 202;
        }else{
            $result = 201;
        }
        return [
            "result" => $result,
            "data" => $output
        ];
    }

    public function network(){
        $server = \request('server');
        $parameters = \request('ip') . ' ' . \request('cidr') . ' ' . \request('gateway') . ' ' . \request('interface');
        $server->systemScript('network',$parameters);
        sleep(3);
        $output = shell_exec("echo exit | telnet " . \request('ip') ." " . $server->port);
        if (strpos($output,"Connected to " . \request('ip')) == false){
            return [
                "result" => 201,
                "data" => $output
            ];
        }
        $server->update([
            'ip_address' => \request('ip')
        ]);
        Key::init($server->key["username"], request('password'), \request('ip'),
            $server->port,Auth::id());
        return [
            "result" => 200,
            "data" => $output
        ];
    }

    public function hostname(){
        $server = \request('server');
        $output = $server->systemScript('hostname',\request('hostname'));
        return [
            "result" => 200,
            "data" => $output
        ];
    }

    public function isAlive(){
        $output = shell_exec("echo exit | telnet " . \request('ip') ." " . \request('port'));
        if (strpos($output,"Connected to " . \request('ip')) == false){
            return [
                "result" => 201,
                "data" => $output
            ];
        }else{
            return [
                "result" => 200,
                "data" => $output
            ];
        }
    }

    public function service(){
        $server = \request('server');
        $service = Extension::where('name','like',\request('extension'))->first()->service;
        $output = $server->run("sudo systemctl " . \request('action') . ' ' . $service);
        return[
            "result" => 200,
            "data" => $output
        ];
    }

    public function enableExtension(){
        $extension = Extension::where('_id',\request('extension_id'))->first();
        $script = Script::where('unique_code',$extension->setup)->first();
        $server = \request('server');
        $output = $server->runScript($script,\request('domain') . " " . \request('interface'));
        if($server->isRunning($extension->service) == "active\n"){
            $server->extensions = array_merge($server->extensions, [\request('extension')]);
            $server->save();
            return [
                "result" => 200,
                "data" => $output
            ];
        }else{
            return [
                "result" => 201,
                "data" => $output
            ];
        }
    }

    public function update(){
        $output = request('server')->update([
            "name" => request('name')
        ]);
        return [
            "result" => 200,
            "data" => $output
        ];
    }
}
