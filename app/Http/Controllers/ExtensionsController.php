<?php

namespace App\Http\Controllers;

use App\Extension;
use App\Script;
use App\Server;
use Illuminate\Http\Request;
use JsonException;
class ExtensionsController extends Controller
{
    public function settings(){
        return view('extensions.index');
    }

    public function one(){
        $extension = Extension::where('_id',\request('id'))->first();
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
        if(!Extension::where('name',\request('feature'))->exists()){
            return redirect(route('home'));
        }
        $servers = Server::where('extensions','like',\request('feature'))->get();
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
        $servers = Server::where('city',\request('city'))->where('extensions','like',\request('feature'))->get();
        return view('feature.city',[
            "servers" => $servers
        ]);
    }

    public function server(){
        $extension = Extension::where('name',\request('extension'))->first();
        $scripts = Script::where('extensions','like',\request('extension'))->get();
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
            "view" => "index"
        ]);
    }

    public function route(){
        $extension = Extension::where('name',\request('extension'))->first();
        $scripts = Script::where('extensions','like',\request('extension'))->get();
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
        $view = (\request()->ajax()) ? 'extensions.' . strtolower(\request('extension')) . '.' . \request('url')
            : 'feature.server';
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
