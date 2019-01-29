<?php

namespace App\Http\Controllers;

use App\Key;
use App\Server;
use Illuminate\Http\Request;

class SshController extends Controller
{
    public static $protected = true;
    
    public function index(){

        // Retrieve User keys.
        $keys = Key::where('user_id',\Auth::id())->get();

        // Retrieve User servers that has permission.
        $servers = Server::getAll();

        return view('keys.index',[
            "keys" => $keys,
            "servers" => $servers
        ]);
    }

    public function add(Request $request){
        
        // Get server object, ps: we cannot use server middleware since we may not have access to server at all.
        $server = Server::where('_id',request('server_id'))->first();

        // Create object with request parameters, acceptable parameters defined in Key $fillable variable.
        $key = new Key($request->all());

        // Set User id of Key.
        $key->user_id = \Auth::id();

        // Init key with parameters.
        $flag = Key::init(request('username'),request('password'),
            $server->ip_address,$server->port,\Auth::id());

        // If key is not usable, cancel operation.
        if(!$flag){
            return respond('SSH Anahtar Hatasi',201);
        }

        // Save Key.
        $key->save();

        // Forward request.
        return respond('SSH Anahtari Basariyla Eklendi',200);
    }
}
