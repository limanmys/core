<?php

namespace App\Http\Controllers\Server;

use App\Http\Controllers\Controller;
use App\Key;
use App\Server;
use Auth;

class AddController extends Controller
{
    public $server;

    public function main(){
        // Create object with parameters.
        $this->server = new Server(request()->all());

        $this->server->user_id = Auth::id();
        $this->server->extensions = [];

        // Check if Server is online or not.
        if(!$this->server->isAlive()){
            return respond("Sunucuyla bağlantı kurulamadı.",406);
        }
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

    private function linux_ssh(){
        // Create Key
        $flag = Key::init(request('username'), request('password'), request('ip_address'),
            request('port'), Auth::id());
        if(!$flag){
            return respond("SSH Hatası",400);
        }

        $this->server->port = request('port');

        $key = new Key(request()->all());

        $key->server_id = $this->server->id;
        $key->user_id = Auth::id();

        $key->save();

        // Validate Key Installation.
        if(!$this->server->sshAccessEnabled()){
            $key->delete();
            $this->server->delete();
            return respond("SSH Hatası",401);
        }

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

    private function grantPermissions(){

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