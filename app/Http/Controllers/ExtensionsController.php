<?php

namespace App\Http\Controllers;

use App\ExtensionSettings;
use App\Extension;
use Illuminate\Http\Request;

class ExtensionsController extends Controller
{
    public function index(){
        return view('extensions.index');
    }

    public function one(){
        $settings = ExtensionSettings::where('extension_id',\request('id'))->first();
        $extension = Extension::where('_id',\request('id'))->first();
        $files = $this->tree(resource_path('views' . DIRECTORY_SEPARATOR .'extensions' . DIRECTORY_SEPARATOR . strtolower($extension->name)));
        return view('extensions.one',[
            "settings" => $settings,
            "extension" => $extension,
            "files" => $files
        ]);
    }

    public function tree($path){
        $files = scandir($path);
        unset($files[0]);
        unset($files[1]);
        $files = array_values($files);
        foreach ($files as $file){
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
}
