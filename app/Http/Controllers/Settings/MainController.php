<?php

namespace App\Http\Controllers\Settings;

use App\Extension;
use App\Permission;
use App\Script;
use App\Server;
use App\User;
use App\Http\Controllers\Controller;

class MainController extends Controller
{

    public function __construct()
    {
        // Specifiy that this controller requires admin middleware in all functions.
        $this->middleware('admin');
    }

    public function index()
    {
        return view('settings.index',[
            "users" => User::all(),
        ]);
    }

    public function one()
    {
        $user = User::find(request('user_id'));

        if(!$user){
            abort(504,"Kullanıcı Bulunamadı.");
        }

        $permissions = Permission::where('user_id',request('user_id'))->first();

        $servers = [];

        foreach ($permissions->server as $server_id){
            $server = Server::find($server_id);
            if($server){
                array_push($servers,$server);
            }
        }

        $scripts = [];

        foreach ($permissions->script as $script_id){
            $script = Script::find($script_id);
            if($script){
                array_push($scripts,$script);
            }
        }

        $extensions = [];

        foreach ($permissions->extension as $extension_id){
            $extension = Extension::find($extension_id);
            if($extension){
                array_push($extensions,$extension);
            }
        }
        return view('settings.one',[
            "user" => $user,
            "servers" => $servers,
            "extensions" => $extensions,
            "scripts" => $scripts
        ]);
    }

    public function getList()
    {
        $permissions = Permission::where('user_id',request('user_id'))->first();
        $data = [];
        $title = [];
        $display = [];
        switch (request('type')){
            case "server":
                $data = Server::whereNotIn('_id',$permissions->server)->get();
                $title = ["*hidden*", "İsim" , "Türü", "İp Adresi"];
                $display = ["_id:_id", "name" , "type", "ip_address"];
                break;
            case "extension":
                $data = Extension::whereNotIn('_id',$permissions->extension)->get();
                $title = ["*hidden*", "İsim"];
                $display = ["_id:_id", "name"];
                break;
            case "script":
                $data = Script::whereNotIn('_id',$permissions->script)->get();
                $title = ["*hidden*", "İsim" , "Eklenti"];
                $display = [ "_id:_id", "name" , "extensions"];
                break;
            default:
                abort(504,"Tip Bulunamadı");
        }
        return view('l.table',[
            "value" => $data,
            "title" => $title,
            "display" => $display,
        ]);
    }

    public function addList()
    {
        try{
            $permissions = Permission::where('user_id',request('user_id'))->first();
            $data = $permissions->__get(request('type'));
            $new = array_merge(json_decode(request('ids')), $data);
            $permissions->__set(request('type'),$new);
            $permissions->save();
            return [
                "message" => "Yetki Başarıyla Verildi.",
                "status" => "yes"
            ];
        }catch (\Exception $exception){
            return [
                "message" => "Yetki Verilemedi.",
                "status" => "no"
            ];
        }
    }
}
