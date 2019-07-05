<?php

namespace App\Http\Controllers\Server;

use App\Http\Controllers\Controller;
use App\Key;
use App\Permission;
use App\Script;
use App\Server;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
            return respond("Bu sunucu ismiyle bir sunucu zaten var.",201);
        }

        // Create object with parameters.
        $this->server = new Server();
        $this->server->fill(request()->all());
//        $this->server = new Server(request()->all());

        $this->server->user_id = auth()->id();

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

    private function linux_ssh()
    {
        $key = new Key(request()->all());

        $key->server_id = $this->server->id;
        $key->user_id = auth()->user()->id;
        $key->save();

        // Create Key
        $flag = \App\Classes\Connector\SSHConnector::create($this->server,request('username'), request('password'),auth()->id(),$key);
        if(!$flag){
            $this->server->delete();
            $key->delete();
            return respond("SSH Hatası",400);
        }

        foreach (extensions() as $extension){
            $script = Script::where('unique_code',strtolower($extension->name) . "_discover")->first();
            if($script){
                try{
                    $output = $this->server->runScript($script,"");
                    if($output == "YES\n"){
                        DB::table('server_extensions')->insert([
                            "id" => Str::uuid(),
                            "server_id" => $this->server()->id,
                            "extension_id" => $extension->id
                        ]);
                    }
                }catch (\Exception $exception){};
            }
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
        $key = new Key(request()->all());

        $key->server_id = $this->server->id;
        $key->user_id = auth()->user()->id;
        $key->save();
        $flag = \App\Classes\Connector\WinRMConnector::create($this->server,request('username'), request('password'),auth()->id(),$key);

        if(!$flag){
            $this->server->delete();
            $key->delete();
            return respond("WinRM Hatası",400);
        }

        return $this->grantPermissions();
    }

    private function grantPermissions()
    {
        $permission = new Permission();
        $permission->server_id = $this->server->id;
        $permission->user_id = auth()->id();
        $permission->save();

        return respond(route('server_one',$this->server->id),300);
    }
}
