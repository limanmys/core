<?php

namespace App\Http\Controllers;

use App\Feature;
use App\Key;
use App\Server;
use App\ServerFeature;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ServerController extends Controller
{
    public function index(){
        return view('server.index',[
            "servers" => Server::all()
        ]);
    }


    public function add(Request $request){
        $data = $request->all();
        $server = new Server($data);
        $server->user_id = Auth::id();
        $server->save();
        Key::init(request('username'), request('password'), request('ip_address'),
            request('port'),Auth::id());
        $key = new Key($data);
        $key->server_id = $server->id;
        $key->user_id = Auth::id();
        $key->save();
        return [
            "result" => 200,
            "id" => $server->id
        ];
    }

    public function remove(){
        Server::where('_id',\request('id'))->delete();
        Key::where('server_id', \request('id'))->delete();
        ServerFeature::where('server_id', \request('id'))->delete();
        return [
            "result" => 200
        ];
    }

    public function one(){
        $server = Server::where('_id',request('id'))->first();
        $server_features = ServerFeature::where('server_id',$server->_id)->get();
        return view('server.one',[
            "stats" => $server->run("df -h"),
            "server" => $server,
            "server_features" => $server_features
        ]);
    }

    public function run(){
        $output = Server::where('_id',\request('server_id'))->first()->run(\request('command'));
        return [
            "result" => 200,
            "data" => $output
        ];
    }
}
