<?php

namespace App\Http\Controllers;

use App\Extension;
use App\Script;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ScriptController extends Controller
{
    public static $protected = true;
    
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
        if($script == false){
            return [
                "result" => 201
            ];
        }
        Storage::putFileAs('scripts',\request()->file('script'),$script->_id);
        return [
            "result" => 200
        ];
    }

    public function one(){
        $script = Script::where('_id',\request('script_id'))->first();
        $contents = Storage::get('scripts/' . $script->_id);
        //Dirty way, but works well.
        $contents = explode("\n", $contents);
        $contents = array_slice($contents,15);
        $contents = implode("\n",$contents);
        return view("scripts.one",[
            "script" => $script,
            "code" => $contents
        ]);
    }

    public function create(){
        $script = new Script();
        $script = Script::createFile($script,["!/usr/bin/python3","-*- coding: utf-8 -*-",request('root'),\request('name'),
        \request('description'),\request('version'),\request('extensions'),\request('inputs'),\request('type'),\Auth::user()->name,\request('support_email'),\request('company'),\request('unique_code'),\request('regex'),\request('code')]);
        $script->save();
        return route('script_one',$script->_id);
    }
}
