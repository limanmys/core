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

        $this->server->user_id = request('user_id');
        $this->server->extensions = [];

        // Check if Server is online or not.
        if(!$this->server->isAlive()){
            return respond("Sunucuyla bağlantı kurulamadı.",406);
        }

        // Run required function for specific type.
        switch ($this->server->type){
            case("linux"):
                return $this->linux();
                break;

            case("linux_ssh"):
                return $this->linux_ssh();
                break;

            case("windows");
                return $this->windows();
                break;

            case("windows_powershell"):
                return $this->windows_powershell();
                break;

            default:
                return respond("Sunucu türü bulunamadı.",404);
                break;
        }
    }

    private function linux_ssh(){
        // Create Key
        Key::init(request('username'), request('password'), request('ip_address'),
            request('port'), request('user_id'));

        // Validate Key Installation.
        if(!$this->server->sshAccessEnabled()){
            return respond("SSH Kullanıcı Parola Hatası",401);
        }

        // Generate Key
        $key = new Key(request()->all());
        $key->server_id = $this->server->id;
        $key->user_id = Auth::id();

        $this->grantPermissions();

        $key->save();

        return respond(route('server_one', $this->server->_id),300);
    }

    private function linux(){
        $this->grantPermissions();
    }

    private function windows(){
        $this->grantPermissions();
    }

    private function windows_powershell(){
        $this->grantPermissions();
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
    }
}