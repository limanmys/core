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

        $permissions = Permission::where('user_id',request('user_id'));

        $servers = Server::find($permissions->whereNotNull("server_id")->pluck("server_id")->toArray());

        $permissions = Permission::where('user_id',request('user_id'));
        $scripts = Script::find($permissions->whereNotNull("script_id")->pluck("script_id")->toArray());

        $permissions = Permission::where('user_id',request('user_id'));
        $extensions = Extension::find($permissions->whereNotNull("extension_id")->pluck("extension_id")->toArray());

        $permissions = Permission::where('user_id',request('user_id'));
        $functions = [];

        foreach ($permissions->whereNotNull("function")->get() as $item){
            array_push($functions,[
                "extension_name" => explode('_',$item->function)[0],
                "name" => explode('_',$item->function)[1]
            ]);
        }

        return view('settings.one',[
            "user" => $user,
            "servers" => $servers,
            "extensions" => $extensions,
            "scripts" => $scripts,
            "functions" => $functions
        ]);
    }

    public function getList()
    {
        $permissions = Permission::where("user_id",request("user_id"));
        $data = [];
        $title = [];
        $display = [];
        switch (request('type')){
            case "server":
                $data = Server::whereNotIn('id',$permissions->whereNotNull("server_id")->pluck("server_id")->toArray())->get();
                $title = ["*hidden*", "İsim" , "Türü", "İp Adresi"];
                $display = ["id:id", "name" , "type", "ip_address"];
                break;
            case "extension":
                $data = Extension::whereNotIn('id',$permissions->whereNotNull("extension_id")->pluck("extension_id")->toArray())->get();
                $title = ["*hidden*", "İsim"];
                $display = ["id:id", "name"];
                break;
            case "script":
                $data = Script::whereNotIn('id',$permissions->whereNotNull("script_id")->pluck("script_id")->toArray())->get();
                $title = ["*hidden*", "İsim" , "Eklenti"];
                $display = [ "id:id", "name" , "extensions"];
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
        foreach(json_decode(request('ids'),true) as $id){
            Permission::grant(request('user_id'),request('type') . "_id",$id);
        }
        return respond(__("Başarılı"),200);
    }

    public function removeFromList()
    {
        foreach(json_decode(request('ids'),true) as $id){
            Permission::revoke(request('user_id'),request('type') . "_id",$id);
        }
        return respond(__("Başarılı"),200);
    }

    public function getExtensionFunctions(){
        $functionsFile = env('EXTENSIONS_PATH') . strtolower(extension()->name) . "/functions.php";
        $allFunctions = [];
        if (is_file($functionsFile)) {
            $functionsFile = file_get_contents(env('EXTENSIONS_PATH') . strtolower(extension()->name) . "/functions.php");
            preg_match_all('/^\s*function (.*)(?=\()/m', $functionsFile, $results);
            $allFunctions = [];
            foreach ($results[1] as $result) {
                array_push($allFunctions, [
                    "name" => $result
                ]);
            }
        }

        $functions = [];
        foreach($allFunctions as $function){
            if(Permission::can(request('user_id'),"function",strtolower(extension()->name) . "_" . $function["name"])){
                continue;
            }
            array_push($functions,[
                "name" => $function["name"]
            ]);
        }

        return view('l.table',[
            "value" => $functions,
            "title" => [
                "Fonksiyon Adı" ,
            ],
            "display" => [
                "name"
            ]
        ]);
    }

    public function addFunctionPermissions(){
        foreach(explode(",",request('functions')) as $function){
             Permission::grant(request('user_id'),"function",strtolower(extension()->name) . "_" . $function);
        }
        return respond(__("Başarılı"),200);
    }

    public function removeFunctionPermissions(){
        foreach(explode(",",request('functions')) as $function){
             Permission::revoke(request('user_id'),"function",$function);
        }
        return respond(__("Başarılı"),200);
    }
}
