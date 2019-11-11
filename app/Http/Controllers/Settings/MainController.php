<?php

namespace App\Http\Controllers\Settings;

use App\Extension;
use App\Permission;
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
            "extensions" => Extension::find($user->permissions->where('type','extension')->pluck('value')->toArray())
        ]);
    }

    public function getUserList()
    {
        return view('l.table',[
            "value" => \App\User::all(),
            "title" => [
                "Kullanıcı Adı" , "Email" , "*hidden*" ,
            ],
            "display" => [
                "name" , "email", "id:user_id" ,
            ],
            "menu" => [
                "Parolayı Sıfırla" => [
                    "target" => "passwordReset",
                    "icon" => "fa-lock"
                ],
                "Sil" => [
                    "target" => "delete",
                    "icon" => " context-menu-icon-delete"
                ]
            ],
            "onclick" => "details"
        ]);
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
