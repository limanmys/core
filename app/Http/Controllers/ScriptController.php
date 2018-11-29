<?php

namespace App\Http\Controllers;

use App\Extension;
use App\Script;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ScriptController extends Controller
{
    public function index(){
        $scripts = Script::all();
        return view( "scripts.index",[
            "scripts" => $scripts
        ]);
    }

    public function add(){
        $features = Extension::all();
        return view("scripts.add",[
            "features" => $features
        ]);
    }

    public function upload(){
        $script = Script::readFromFile(\request()->file('script'));
        Storage::putFileAs('scripts',\request()->file('script'),$script->_id);
        return [
            "result" => 200
        ];
    }

    public function one(){
        $script = Script::where('_id',\request('id'))->first();
        $contents = Storage::get('scripts/' . $script->_id);
        //Dirty way, but works well.
        $contents = explode("\n", $contents);
        $contents = array_slice($contents,14);
        $contents = implode("\n",$contents);
        return view("scripts.one",[
            "script" => $script,
            "code" => $contents
        ]);
    }

    public function create(){
        $script = new Script();
        $script = Script::fillValues($script,\request('language'),\request('encoding'),\request('root'),\request('name'),
        \request('description'),\request('version'),\request('extensions'),\request('inputs'),\request('outputs')
    ,\request('type'),\request('authors'),\request('support_email'),\request('company'),\request('unique_code'),\request('code'));
        $script->save();
        return [
            "result" => 200,
            "script" => $script->_id
        ];
    }
}
