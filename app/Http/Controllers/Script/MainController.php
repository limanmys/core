<?php

namespace App\Http\Controllers\Script;

use App\Extension;
use App\Script;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\Storage;

class MainController extends Controller
{
    public function index(){
        $scripts = Script::all();
        return view( "scripts.index",[
            "scripts" => $scripts
        ]);
    }

    public function add(){
        $extensions = Extension::all();
        return view("scripts.add",[
            "extensions" => $extensions
        ]);
    }

    public function upload(){
        $script = Script::readFromFile(\request()->file('script'));
        if(!$script){
            return respond('Betik zaten sistemde var.',201);
        }
        Storage::putFileAs('scripts',\request()->file('script'),$script->_id);
        return respond('Betik eklendi.',200);
    }

    public function one()
    {
        $script = Script::where('_id',\request('script_id'))->first();
        try{
            $contents = Storage::get('scripts/' . $script->_id);
        }catch (FileNotFoundException $exception){
            return respond('Dosya Bulanamadı',404);
        }
        //Dirty way, but works well.
        $contents = explode("\n", $contents);
        $contents = array_slice($contents,15);
        $contents = implode("\n",$contents);
        return view("scripts.one",[
            "script" => $script,
            "code" => $contents
        ]);
    }

    public function create()
    {
        $script = new Script();
        $script = Script::createFile($script,["!/usr/bin/python3","-*- coding: utf-8 -*-",request('root'),\request('name'),
            \request('description'),\request('version'),\request('extensions'),\request('inputs'),\request('type'),auth()->user()->name,\request('support_email'),\request('company'),\request('unique_code'),\request('regex'),\request('code')]);
        $script->save();
        return route('script_one',$script->_id);
    }

    public function download()
    {
        $script = Script::where('_id', request('script_id'))->first();

        // Send file to the user then delete it.
        return response()->download(storage_path('app/scripts/') . $script->_id, $script->unique_code . '.lmns');
    }

    public function delete()
    {
        // Get Script
        shell_exec('rm ' .storage_path('app/scripts/' . script()->_id));
        script()->delete();
        return respond("Betik başarıyla silindi",200);
    }

}
