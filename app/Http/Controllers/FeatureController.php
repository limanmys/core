<?php

namespace App\Http\Controllers;

use App\Feature;
use App\Script;
use App\Server;
use Illuminate\Http\Request;

class FeatureController extends Controller
{

//    'feature' => \request('feature')

    public function index(){
        if(!Feature::where('name',\request('feature'))->exists()){
            return redirect(route('home'));
        }
        $cities = "54,81,06";
        return view('feature.index',[
            "cities" => $cities
        ]);
    }

    public function city(){
        $servers = Server::where('city',\request('city'))->where('features','like',\request('feature'))->get();
        return view('feature.city',[
            "servers" => $servers
        ]);
    }

    public function server(){
        $feature = Feature::where('name',\request('feature'))->first();
        $server = Server::where('_id',\request('server'))->first();
        $scripts = Script::where('features','like',\request('feature'))->get();
        $script = Script::where('_id',"5bf3e497fdce2716bf55930d")->first();
        $output = $server->runScript($script,"");
        return view('feature.server',[
            "feature" => $feature,
            "server" => $server,
            "scripts" => $scripts,
            "output" => $output
        ]);
    }

}
