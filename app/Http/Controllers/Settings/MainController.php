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

    public function one(User $user)
    {
        return view('settings.one',[
            "user" => $user,
            "servers" => Server::find($user->permissions->where('type','server')->pluck('value')->toArray()),
            "extensions" => Extension::find($user->permissions->where('type','extension')->pluck('value')->toArray()),
            "scripts" => Script::find($user->permissions->where('type','script')->pluck('value')->toArray())
        ]);

        // $servers = Server::find($permissions->whereNotNull("server_id")->pluck("server_id")->toArray());

        // $permissions = Permission::where('user_id',request('user_id'));
        // $scripts = Script::find($permissions->whereNotNull("script_id")->pluck("script_id")->toArray());

        // $permissions = Permission::where('user_id',request('user_id'));
        // $extensions = Extension::find($permissions->whereNotNull("extension_id")->pluck("extension_id")->toArray());

        // $permissions = Permission::where('user_id',request('user_id'));
        // $functions = [];

        // foreach ($permissions->whereNotNull("function")->get() as $item){
        //     $functionsFile = env('EXTENSIONS_PATH') . strtolower(explode('_',$item->function)[0]) . "/views/functions.php";
        //     $comments = $this->getComments($functionsFile);
        //     foreach ($comments as $comment){
        //         if(!array_key_exists("LimanName",$comment) || !array_key_exists("LimanPermission",$comment)
        //             || !array_key_exists("LimanFunction",$comment)){
        //             abort(504,"Eklenti Duzgun Yapilandirilmamis");
        //         }
        //         if(explode('_',$item->function)[1] == $comment["LimanFunction"]){
        //             array_push($functions,[
        //                 "extension_name" => explode('_',$item->function)[0],
        //                 "name" => $comment["LimanName"],
        //                 "db_name" => $item->function
        //             ]);
        //             break;
        //         }
        //     }
        // }

        // return view('settings.one',[
        //     "user" => user(),
        //     "servers" => $servers,
        //     "extensions" => $extensions,
        //     "scripts" => $scripts,
        //     "functions" => $functions
        // ]);
    }

    public function getList()
    {
        $user = User::find(request('user_id'));
        $data = [];
        $title = [];
        $display = [];
        switch (request('type')){
            case "server":
                $data = Server::whereNotIn('id',$user->permissions->where('type','server')->pluck('value')->toArray())->get();
                $title = ["*hidden*", "İsim" , "Türü", "İp Adresi"];
                $display = ["id:id", "name" , "type", "ip_address"];
                break;
            case "extension":
                $data = Extension::whereNotIn('id',$user->permissions->where('type','extension')->pluck('value')->toArray())->get();
                $title = ["*hidden*", "İsim"];
                $display = ["id:id", "name"];
                break;
            case "script":
                $data = Script::whereNotIn('id',$user->permissions->where('type','script')->pluck('value')->toArray())->get();
                $title = ["*hidden*", "İsim" , "Eklenti"];
                $display = [ "id:id", "name" , "extensions"];
                break;
            case "liman":
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
            Permission::grant(request('user_id'),request('type'),"id",$id);
        }
        return respond(__("Başarılı"),200);
    }

    public function removeFromList()
    {
        foreach(json_decode(request('ids'),true) as $id){
            Permission::revoke(request('user_id'),request('type'),"id",$id);
        }
        return respond(__("Başarılı"),200);
    }

    public function getExtensionFunctions()
    {
        $extension = json_decode(file_get_contents(env("EXTENSIONS_PATH") .strtolower(extension()->name) . DIRECTORY_SEPARATOR . "db.json"),true);
        $functions = array_key_exists("functions",$extension) ? $extension["functions"] : [];
        $lang = session('locale');
        $file = env('EXTENSIONS_PATH') . strtolower(extension()->name) . "/lang/" . $lang . ".json";

        //Translate Items.
        $cleanFunctions = [];
        if(is_file($file)){
            $json = json_decode(file_get_contents($file),true);
            for($i = 0; $i < count($functions); $i++){
                if(array_key_exists("isActive",$functions[$i]) && $functions[$i]["isActive"] == "false"){
                    continue;
                }
                $description = (array_key_exists($functions[$i]["description"],$json)) 
                    ? $json[$functions[$i]["description"]] : $functions[$i]["description"];
                array_push($cleanFunctions,[
                    "name" => $functions[$i]["name"],
                    "description" => $description
                ]);
                
            }
        }
        
        return view('l.table',[
            "value" => $cleanFunctions,
            "title" => [
                "*hidden*" , "Aciklama"
            ],
            "display" => [
                "name:name", "description"
            ]
        ]);
    }

    public function addFunctionPermissions(){
        foreach(explode(",",request('functions')) as $function){
             Permission::grant(request('user_id'),"function","name",strtolower(extension()->name),$function);
        }
        return respond(__("Başarılı"),200);
    }

    public function removeFunctionPermissions(){
        foreach(explode(",",request('functions')) as $function){
             Permission::find($function)->delete();
        }
        return respond(__("Başarılı"),200);
    }

    public function health()
    {
        return respond(checkHealth(),200);
    }
}
