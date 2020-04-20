<?php

use App\User;
use App\Module;
use App\ModuleHook;
use App\AdminNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

Artisan::command('administrator',function (){

    // Generate Password
    do{
        $pool = str_shuffle('abcdefghjklmnopqrstuvwxyzABCDEFGHJKLMNOPQRSTUVWXYZ234567890!$@%^&!$%^&');
        $password = substr($pool,0,10);
    }while(!preg_match("/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{10,}$/", $password));
    $user = User::where([
        "name" => "Administrator",
        "email" => "administrator@liman.app"
    ])->first();
    if($user){
        $user->update([
            "password" => Hash::make($password)
        ]);
    }else{
        $user = new User();
        $user->fill([
            "name" => "Administrator",
            "email" => "administrator@liman.app",
            "password" => Hash::make($password),
            "status" => 1,
        ]);
    }
    $user->save();

    $this->comment("Liman MYS Administrator Kullanıcısı");
    $this->comment("Email  : administrator@liman.app");
    $this->comment("Parola : " . $password . "");
})->describe('Create administrator account to use');


Artisan::command('scan:translations',function (){
    if(!env("EXTENSION_DEVELOPER_MODE")){
        return $this->error("You need to open extension developer mode for use this function.");
    }
    $extension_path = env("EXTENSIONS_PATH");
    $extensions = glob($extension_path.'/*', GLOB_ONLYDIR);
    $this->info("Started to scanning extension folders.");
    foreach($extensions as $extension){
        $this->comment("Scanning: ".$extension);
        $output = "$extension/lang/en.json";
        $translations = scanTranslations($extension);
        if (!is_dir(dirname($output))) {
            mkdir(dirname($output));
        }
        if(is_file($output)){
            $translations = array_merge($translations, (array)json_decode(file_get_contents($output)));
        }
        file_put_contents($output, json_encode($translations, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        $this->info("Scanned and saved to ".$output);
    }
    $this->info("Finished scanning extension folders.");

    $this->info("Started to scanning server files.");
    $server_path = env("SERVER_PATH");
    $this->comment("Scanning: ".$server_path);
    $output = "$server_path/resources/lang/en.json";
    $translations = scanTranslations($server_path);
    if(is_file($output)){
        $translations = array_merge($translations, (array)json_decode(file_get_contents($output)));
    }
    file_put_contents($output, json_encode($translations, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    $this->comment("Scanned and saved to ".$output);
})->describe('Scan missing translation strings');

Artisan::command('module:add {module_name}',function($module_name){
    
    // Check if files are exists.
    if(!is_dir("/liman/modules/$module_name")){
        return $this->error("Modul klasoru bulunamadi!");
    }

    if(!is_file("/liman/modules/$module_name/main") || !is_file("/liman/modules/$module_name/template.json")){
        return $this->error("Modul gecerli degil.");
    }

    $this->info("$module_name modulu ekleniyor.");

    //Let's read the template.
    $template = file_get_contents("/liman/modules/$module_name/template.json");
    $template = json_decode($template,true);

    if(json_last_error() != JSON_ERROR_NONE){
        return $this->error("Modul ayar dosyasi anlasilmadi, lutfen modul yoneticisiyle iletisime gecin");
    }

    // Check if module already exists.
    if(Module::where('name',$module_name)->exists()){
        return $this->error("Bu isimde bir modul zaten ekli.");
    }

    $module = new Module(["name" => $module_name]);
    $module->save();

    // Let's check module hooks.
    $listen = $template["hooks"]["listen"];
    $dbArray = [];

    $now = Carbon::now('utc')->toDateTimeString();

    foreach ($listen as $value) {
        array_push($dbArray,[
            "hook" => $value,
            "id" => Str::uuid(),
            "module_id" => $module->id,
            "module_name" => $module->name,
            "enabled" => false,
            "created_at" => $now,
            "updated_at" => $now
        ]);
    }

    $flag = ModuleHook::insert($dbArray);

    if($flag){
        $notification = new AdminNotification([
            "title" => "Yeni Modül Eklendi",
            "type" => "new_module",
            "message" => "$module->name isminde bir modül sisteme eklendi.",
            "level" => 3
        ]);
        $notification->save();
        shell_exec("chmod +x /liman/modules/" . $module->name . "/main");
        $this->info("Modul basariyla yuklendi, lutfen liman arayuzunden yetkilerini onaylayin.");
    }else{
        $this->error("Modul yuklenemedi, bir hata olustu.\n$flag");
    }

})->describe("New module add");

Artisan::command('module:remove {module_id}',function($module_id){
    $module = Module::find($module_id)->first();

    if(!$module){
        return $this->error("Modul bulunamadi!");
    }

    $flag = $module->delete();

    if($flag){
        $this->info("Modul basariyla silindi.");
    }else{
        $this->error("Modul silinemedi.$flag");
    }

})->describe("Module remove");