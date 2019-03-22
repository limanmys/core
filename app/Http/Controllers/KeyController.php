<?php

namespace App\Http\Controllers;

use App\Classes\Connector\SSHConnector;
use App\Key;
use App\Server;

class KeyController extends Controller
{
    public static $protected = true;
    
    public function index(){

        // Retrieve User keys.
        $keys = Key::where('user_id',auth()->id())->get();

        // Retrieve User servers that has permission.
        $servers = Server::getAll();
        $servers = $servers->where('type','linux_ssh');
        foreach ($keys as $key){
            $server = $servers->where('_id',$key->server_id)->first();
            $key->server_name = ($server) ? $server->name : __("Sunucu Silinmiş.");
        }

        return view('keys.index',[
            "keys" => $keys,
            "servers" => $servers
        ]);
    }

    public function add()
    {
        if(Key::where([
            'user_id' => auth()->id(),
            'server_id' => request('server_id')
        ])->first()){
            return respond('Zaten bir anahtariniz var.',201);
        }

        // Create object with request parameters, acceptable parameters defined in Key $fillable variable.
        $key = new Key(request()->all());

        // Set User id of Key.
        $key->user_id = auth()->id();

        $key->save();

        // Init key with parameters.
        if(request('server')->type == "linux_ssh"){
            try{
                $flag = SSHConnector::create(request('server'),request('username'),request('password'),auth()->id(),$key);
            }catch (\Exception $exception){
                $flag = "Bilinmeyen bir hata oluştu.";
            }
        }
        if($flag != "OK"){
            $key->delete();
            return respond($flag,201);
        }

        // Forward request.
        return respond('Anahtar Basariyla Eklendi',200);
    }

    public function delete()
    {
        \App\Key::where('_id',request('key_id'))->delete();
        return respond("Anahtar Silindi");
    }
}
