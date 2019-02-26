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

        return view('keys.index',[
            "keys" => $keys,
            "servers" => $servers
        ]);
    }

    public function add()
    {
        // Create object with request parameters, acceptable parameters defined in Key $fillable variable.
        $key = new Key(request()->all());

        // Set User id of Key.
        $key->user_id = auth()->id();

        // Init key with parameters.
        if(request('server')->type == "linux_ssh"){
            SSHConnector::create(request('server'),request('username'),request('password'),auth()->id());
        }

        // Save Key.
        $key->save();

        // Forward request.
        return respond('SSH Anahtari Basariyla Eklendi',200);
    }
}
