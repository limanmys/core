<?php

namespace App\Http\Controllers;

use App\Extension;
use App\Script;
use App\Server;
use Illuminate\Http\Request;

class ExtensionController extends Controller
{
    public static $protected = true;
    
    public function settings(){
        return view('extensions.index');
    }

    public function one(){
        $extension = Extension::where('_id',\request('extension_id'))->first();
        $files = $this->tree(resource_path('views' . DIRECTORY_SEPARATOR .'extensions' . DIRECTORY_SEPARATOR . strtolower($extension->name)));
        return view('extensions.one',[
            "extension" => $extension,
            "files" => $files
        ]);
    }

    public function tree($path){
        if(!is_dir($path)){
            return [];
        }
        $files = scandir($path);
        unset($files[0]);
        unset($files[1]);
        $files = array_values($files);
        foreach ($files as $file){
            $folder = pathinfo($path)["basename"];
            $newPath = $path . DIRECTORY_SEPARATOR . $file;
            if(is_dir($newPath)){
                $folder_name = pathinfo($newPath)["basename"];
                $files[$folder_name] = $this->tree($path . DIRECTORY_SEPARATOR . $file);
                $index = array_search($folder_name,$files);
                unset($files[$index]);
            }
        }
        return $files;
    }

    public function index(){
        if(Extension::where('_id',request('extension_id'))->exists() == false){
            return redirect(route('home'));
        }
        $servers = Server::where('extensions','like',\request('extension_id'))->get();
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
            "name" => request('extension')
        ]);
    }

    public function city(){
        $servers = Server::where('city',\request('city'))->where('extensions','like',\request('extension_id'))->get();
        return view('feature.city',[
            "servers" => $servers
        ]);
    }

    public function server(){
        $extension = Extension::where('_id',\request('extension_id'))->first();
        $scripts = Script::where('extensions','like',$extension->name)->get();
        $server = \request('server');
        $outputs = [];
        foreach ($extension->views["index"] as $unique_code){
            $script = $scripts->where('unique_code', $unique_code)->first();
            $output = $server->runScript($script,'');
            $output = str_replace('\n','',$output);
            $outputs[$unique_code] = json_decode($output,true);
        }
        return view('feature.server',[
            "extension" => $extension,
            "scripts" => $scripts,
            "data" => $outputs,
            "view" => "index",
            "server" => $server
        ]);
    }

    public function route(){
        $extension = request('extension');
        $scripts = Script::where('extensions','like','%' . $extension->name . '%')->get();
        $outputs = [];
        foreach (\request('scripts') as $script){
            $parameters = '';
            foreach (explode(',' , $script->inputs) as $input){
                $parameters = $parameters . " " .\request(explode(':', $input)[0]);
            }
            $output = \request('server')->runScript($script,$parameters);
            $output = str_replace('\n','',$output);
            $outputs[$script->unique_code] = json_decode($output,true);
        }
        $view = (\request()->ajax()) ? 'extensions.' . strtolower(\request('extension_id')) . '.' . \request('url') : 'feature.server';
        if(view()->exists($view)){
            return view($view,[
                "result" => 200,
                "data" => $outputs,
                "view" => \request('url'),
                "extension" => $extension,
                "scripts" => $scripts,
            ]);
        }else{
            return [
                "result" => 200,
                "data" => $outputs,
                "view" => \request('url'),
                "extension" => $extension,
                "scripts" => $scripts,
            ];
        }
    }

}
