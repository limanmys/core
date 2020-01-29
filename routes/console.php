<?php

use App\User;
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