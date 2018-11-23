<?php

namespace App\Http\Controllers;

use App\Extension;
use App\Script;
use App\Server;

class FeatureController extends Controller
{

//    'feature' => \request('feature')

    public function index(){
        if(!Extension::where('name',\request('feature'))->exists()){
            return redirect(route('home'));
        }
        $servers = Server::where('features','like',\request('feature'))->get();
        $cities = "";
        foreach ($servers as $server){
            if($cities == "")
                $cities = $cities . $server->city;
            else{
                $cities = $cities . "," .$server->city;
            }
        }
        return view('feature.index',[
            "cities" => $cities,
            "name" => request('feature')
        ]);
    }

    public function city(){
        $servers = Server::where('city',\request('city'))->where('features','like',\request('feature'))->get();
        return view('feature.city',[
            "servers" => $servers
        ]);
    }

    public function server(){
        $feature = Extension::where('name',\request('feature'))->first();
        $server = Server::where('_id',\request('server'))->first();
        $scripts = Script::where('features','like',\request('feature'))->get();
        $script = Script::where('_id',"5bf69cc7fdce27729a1080c2")->first();
        $output = $server->runScript($script,"");
        $output = str_replace("\n","",$output);
        $json = json_decode($output,true);
        return view('feature.server',[
            "feature" => $feature,
            "server" => $server,
            "scripts" => $scripts,
            "output" => $json
        ]);
    }

}
