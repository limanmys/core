<?php

namespace App\Http\Controllers\Server;

use App\Http\Controllers\Controller;
use App\Key;
use App\Script;
use App\Server;
use Auth;

class AddController extends Controller
{
    /**
     * @var \App\Server
     */
    public $server;

    public function main()
    {
        // Check if name is already in use.
        if(Server::where([
            'user_id' => auth()->id(),
            "name" => request('name')
        ])->exists()){
            return respond("Bu sunucu ismiyle bir sunucunuz zaten var.",201);
        }

        // Create object with parameters.
        $this->server = new Server(request()->all());

        $this->server->user_id = Auth::id();
        $this->server->extensions = [];

        // Check if Server is online or not.
        if(!$this->server->isAlive()){
            return respond("Sunucuyla bağlantı kurulamadı.",406);
        }
        $this->server->port = request('port');
        $this->server->save();

        // Add Server to request object to use it later.
        request()->request->add(["server" => $this->server]);

        // Run required function for specific type.
        $next = null;
        switch ($this->server->type){
            case("linux"):
                $next = $this->linux();
                break;

            case("linux_ssh"):
                $next = $this->linux_ssh();
                break;

            case("windows");
                $next = $this->windows();
                break;

            case("windows_powershell"):
                $next = $this->windows_powershell();
                break;

            default:
                $next = respond("Sunucu türü bulunamadı.",404);
                break;
        }
        return $next;
    }

    private function linux_ssh()
    {
        $key = new Key(request()->all());

        $key->server_id = $this->server->id;
        $key->user_id = Auth::id();
        $key->save();

        // Create Key
        $flag = \App\Classes\Connector\SSHConnector::create($this->server,request('username'), request('password'),auth()->id(),$key);
        if(!$flag){
            $key->delete();
            return respond("SSH Hatası",400);
        }

        foreach (extensions() as $extension){
            $script = Script::where('unique_code',strtolower($extension->name) . "_discover")->first();
            if($script){
                try{
                    $output = $this->server->runScript($script,"");
                }catch (\Exception $exception){};
                if($output == "yes\n"){
                    $extensions_array = $this->server->extensions;
                    $extensions_array[$extension->_id] = [];
                    $this->server->extensions = $extensions_array;
                    $this->server->save();
                }
            }
        }
//        foreach(extensions() as $extension){
//            if($this->server->run("(systemctl list-units | grep $extension->service  && echo \"OK\" || echo \"NOK\") | tail -1") != "OK\n"){
//                $extensions_array = $this->server->extensions;
//                $extensions_array[$extension->_id] = [];
//                $this->server->extensions = $extensions_array;
//                $this->server->save();
//            }
//        };

        return $this->grantPermissions();
    }

    private function linux(){
        return $this->grantPermissions();
    }

    private function windows(){
        return $this->grantPermissions();
    }

    private function windows_powershell(){
        return $this->grantPermissions();
    }

    private function grantPermissions()
    {
        // Give User a permission to use this server.
        $permissions = request('permissions');
        $user_servers = (Array) $permissions->server;
        array_push($user_servers, $this->server->_id);
        $permissions->server = $user_servers;

        // Lastly, save all information.
        $permissions->save();
        $this->server->save();

        return respond(route('server_one',$this->server->_id),300);
    }
}
