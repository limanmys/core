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
            $functionsFile = env('EXTENSIONS_PATH') . strtolower(explode('_',$item->function)[0]) . "/views/functions.php";
            $comments = $this->getComments($functionsFile);
            foreach ($comments as $comment){
                if(!array_key_exists("LimanName",$comment) || !array_key_exists("LimanPermission",$comment)
                    || !array_key_exists("LimanFunction",$comment)){
                    abort(504,"Eklenti Duzgun Yapilandirilmamis");
                }
                if(explode('_',$item->function)[1] == $comment["LimanFunction"]){
                    array_push($functions,[
                        "extension_name" => explode('_',$item->function)[0],
                        "name" => $comment["LimanName"],
                        "db_name" => $item->function
                    ]);
                    break;
                }
            }
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
        $functionsFile = env('EXTENSIONS_PATH') . strtolower(extension()->name) . "/views/functions.php";
        $comments = $this->getComments($functionsFile);
        $functions = [];

        foreach ($comments as $comment){
            if(!array_key_exists("LimanName",$comment) || !array_key_exists("LimanPermission",$comment)
                || !array_key_exists("LimanFunction",$comment)){
                abort(504,"Eklenti Duzgun Yapilandirilmamis");
            }
            if($comment["LimanPermission"] != "true"){
                continue;
            }
            if(Permission::can(request('user_id'),"function",strtolower(extension()->name) . "_" . $comment["LimanFunction"])){
                continue;
            }
            array_push($functions,[
                "name" => $comment["LimanFunction"],
                "function" => $comment["LimanName"]
            ]);
        }

        return view('l.table',[
            "value" => $functions,
            "title" => [
                "Fonksiyon Adı" , "*hidden*"
            ],
            "display" => [
                "function", "name:name"
            ]
        ]);
    }

    private function getComments($path)
    {
        $cleaner = [];
        foreach ($this->getFileDocBlock($path) as $item){
            $rows = explode("\n",$item);
            $current = [];
            foreach ($rows as $row){
                if(strpos($row,"@Liman")){
                    $toParse = substr($row,strpos($row,"@Liman"));
                    $current[substr(explode(" ",$toParse)[0],1)]
                        = substr($toParse,strlen(substr(explode(" ",$toParse)[0],0)) +1 );
                }
            }
            array_push($cleaner,$current);
        }
        return $cleaner;
    }

    private function getFileDocBlock($file)
    {
        $docComments = array_filter(
            token_get_all( file_get_contents( $file ) ), function($entry) {
            return $entry[0] == T_DOC_COMMENT;
        });
        $clean = [];
        foreach ($docComments as $item){
            array_push($clean,$item[1]);
        }
        return $clean;
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

    public function health()
    {
        $allowed = [
            "certs" => "0700",
            "database" => "0700",
            "extensions" => "0755",
            "keys" => "0755",
            "logs" => "0700",
            "sandbox" => "0755",
            "server" => "0700",
            "keys/linux" => "0700",
            "keys/windows" => "0700"
        ];
        $messages = [];

        // Check Permissions
        foreach ($allowed as $name=>$permission){
            if(getPermissions('/liman/' . $name) != $permission){
                array_push($messages,[
                    "type" => "danger",
                    "message" => "'/liman/$name' izni hatali (". getPermissions('/liman/' . $name) .")."
                ]);
            }
        }

        // Check Extra Files
        $extra = array_diff(array_diff(scandir("/liman"),array('..','.')),array_keys($allowed));
        foreach ($extra as $item){
            array_push($messages,[
                "type" => "warning",
                "message" => "'/liman/$item' dosyasina izin verilmiyor."
            ]);
        }
        if(empty($messages)){
            array_push($messages,[
                "type" => "success",
                "message" => "Herşey Yolunda!"
            ]);
        }
        return respond($messages,200);
    }
}
