<?php

namespace App\Http\Controllers\Settings;

use App\Extension;
use App\Permission;
use App\Script;
use App\Server;
use App\User;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

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

        $functions = [];

        foreach ($permissions->function as $function){
            array_push($functions,[
                "extension_name" => explode('_',$function)[0],
                "name" => explode('_',$function)[1]
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
        foreach(json_decode(request('ids'),true) as $id){
            Permission::grant(request('user_id'),request('type'),$id);
        }
        return respond(__("Başarılı"),200);
    }

    public function removeFromList()
    {
        foreach(json_decode(request('ids'),true) as $id){
            Permission::revoke(request('user_id'),request('type'),$id);
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
