<?php

namespace App\Http\Controllers\Settings;

use App\Extension;
use App\Permission;
use App\Server;
use App\User;
use App\Role;
use App\Http\Controllers\Controller;
use App\AdminNotification;
use App\Certificate;
use App\RoleMapping;
use App\RoleUser;
use App\PermissionData;
use App\ServerGroup;

class MainController extends Controller
{

    public function __construct()
    {
        // Specifiy that this controller requires admin middleware in all functions.
        $this->middleware('admin');
    }

    public function index()
    {
        $changelog = is_file(storage_path('changelog')) ? file_get_contents(storage_path('changelog')) : "";
        return view('settings.index',[
            "users" => User::all(),
            "changelog" => $changelog
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
            case "role":
                $data = Role::whereNotIn('id',$user->roles->pluck('id')->toArray())->get();
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
        $arr = [];
        foreach(json_decode(request('ids'),true) as $id){
            array_push($arr,$id);
            Permission::grant(request('user_id'),request('type'),"id",$id);
        }
        $arr["type"] = request('type');
        $arr["target_user_id"] = request('user_id');
        system_log(7,"PERMISSION_GRANT",$arr);
        return respond(__("Başarılı"),200);
    }

    public function removeFromList()
    {
        $arr = [];
        foreach(json_decode(request('ids'),true) as $id){
            Permission::revoke(request('user_id'),request('type'),"id",$id);
        }
        array_push($arr,$id);
        $arr["type"] = request('type');
        $arr["target_user_id"] = request('user_id');
        system_log(7,"PERMISSION_REVOKE",$arr);
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

    public function saveLDAPConf()
    {
        $cert = Certificate::where([
            'server_hostname' => request('ldapAddress'),
            'origin' => 636
        ])->first();
        if(!$cert){
            list($flag, $message) = retrieveCertificate(request('ldapAddress'),636);
            if($flag){
                addCertificate(request('ldapAddress'),636,$message["path"]);
                $notification = new AdminNotification();
                $notification->title = "Yeni Sertifika Eklendi";
                $notification->type = "new_cert";
                $notification->message = "Sisteme yeni sunucu eklendi ve yeni bir sertifika eklendi.<br><br><a href='" . route('settings') . "#certificates'>Detaylar</a>";
                $notification->level = 3;
                $notification->save();
            }
        }
        if(!setBaseDn(request('ldapAddress'))){
            return respond('Sunucuya bağlanırken bir hata oluştu!', 201);
        }
        if(request('ldapAddress') !== config('ldap.ldap_host')){
            RoleMapping::truncate();
            User::where("auth_type", "ldap")->get()->map(function($item){
                $item->permissions()->delete();
            });
            User::where("auth_type", "ldap")->get()->map(function($item){
                RoleUser::where("user_id", $item->id)->delete();
            });
            User::where("auth_type", "ldap")->delete();
        }
        setEnv([
            "LDAP_HOST" => request('ldapAddress'),
            "LDAP_GUID_COLUMN" => request('ldapObjectGUID'),
        ]);
        return respond(__("Kaydedildi!"),200);
    }

    public function getPermisssionData()
    {
        $data = PermissionData::where('permission_id',request('id'))->first();
        if(!$data){
            return respond("");
        }
        return respond($data->data);
    }

    public function writePermisssionData()
    {
        $data = PermissionData::where('permission_id',request('id'))->first();
        if($data){
            $data->update([
                "data" => json_encode(request('data'))
            ]);
            return respond("Başarıyla eklendi!");
        }
        
        $obj = new PermissionData();
        $obj->data = json_encode(request('data'));
        $obj->permission_id = request('id');
        $obj->save();
        return respond("Başarıyla eklendi!");
    }

    public function addServerGroup()
    {
        if(ServerGroup::where('name',request('name'))->exists()){
            return respond("Bu isimle zaten bir grup var.",201);
        }
        $group = new ServerGroup([
            "name" => request('name'),
            "servers" => request('servers')
        ]);
        $flag = $group->save();
        return ($flag) ? respond("Grup başarıyla eklendi!") : respond("Grup Eklenemedi!",201);
    }

    public function modifyServerGroup()
    {
        $group = ServerGroup::find(request('server_group_id'));
        if(!$group){
            return respond("Grup bulunamadı!",201);
        }
        $group->update([
            "name" => request('name'),
            "servers" => request('servers')
        ]);
        $flag = $group->save();
        return ($flag) ? respond("Grup başarıyla düzenlendi!") : respond("Grup Düzenlenemedi!",201);
    }

    public function deleteServerGroup()
    {
        $group = ServerGroup::find(request('server_group_id'));
        if(!$group){
            return respond("Grup bulunamadı!",201);
        }
        $flag = $group->delete();
        return ($flag) ? respond("Grup başarıyla silindi!") : respond("Grup Silinemedi!",201);
    }
}
