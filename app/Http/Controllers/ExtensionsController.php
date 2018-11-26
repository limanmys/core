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
        $extension = Extension::where('name',\request('feature'))->first();
        $scripts = Script::where('extensions','like',\request('feature'))->get();
            return view('feature.server',[
            "extension" => $extension,
            "scripts" => $scripts,
        ]);
    }

    public function generatePage(){
        foreach (\request('scripts') as $script){
            foreach (str_split($script->inputs,',') as $input){
                
            }
        }
    }

    public function route(){
        if(!file_exists(resource_path('views') . DIRECTORY_SEPARATOR . 'extensions' . DIRECTORY_SEPARATOR .
         request('feature') . DIRECTORY_SEPARATOR . request('route') )){
            return view('general.error',[
               'Route bulunamadı!'
            ]);
        }
        //TODO
    }
}
