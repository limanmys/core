<?php

use App\User;
use App\Models\Module;
use App\Models\AdminNotification;
use App\Models\Extension;
use App\Models\Liman;
use App\Models\SystemSettings;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

Artisan::command('administrator', function () {
    // Generate Password
    do {
        $pool = str_shuffle(
            'abcdefghjklmnopqrstuvwxyzABCDEFGHJKLMNOPQRSTUVWXYZ234567890!$@%^&!$%^&'
        );
        $password = substr($pool, 0, 10);
    } while (
        !preg_match(
            "/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{10,}$/",
            $password
        )
    );
    $user = User::where([
        "name" => "Administrator",
        "email" => "administrator@liman.dev",
    ])->first();
    if ($user) {
        $user->update([
            "password" => Hash::make($password),
        ]);
    } else {
        $user = new User();
        $user->fill([
            "name" => "Administrator",
            "email" => "administrator@liman.dev",
            "password" => Hash::make($password),
            "status" => 1,
        ]);
    }
    $user->save();

    $this->comment("Liman MYS Administrator Kullanıcısı");
    $this->comment("Email  : administrator@liman.dev");
    $this->comment("Parola : " . $password . "");
})->describe('Create administrator account to use');

Artisan::command('scan:translations', function () {
    if (env('EXTENSION_DEVELOPER_MODE') != true) {
        return $this->error(
            "You need to open extension developer mode for use this function."
        );
    }
    $extension_path = "/liman/extensions/";
    $extensions = glob($extension_path . '/*', GLOB_ONLYDIR);
    $this->info("Started to scanning extension folders.");
    foreach ($extensions as $extension) {
        $this->comment("Scanning: " . $extension);
        $output = "$extension/lang/en.json";
        $translations = scanTranslations($extension);
        if (!is_dir(dirname($output))) {
            mkdir(dirname($output));
        }
        if (is_file($output)) {
            $translations = (object) array_merge(
                $translations,
                (array) json_decode(file_get_contents($output))
            );
        }
        file_put_contents(
            $output,
            json_encode(
                $translations,
                JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
            )
        );
        $this->info("Scanned and saved to " . $output);
    }
    $this->info("Finished scanning extension folders.");

    $this->info("Started to scanning server files.");
    $server_path = "/liman/server";
    $this->comment("Scanning: " . $server_path);
    $output = "$server_path/resources/lang/en.json";
    $translations = scanTranslations($server_path);
    if (is_file($output)) {
        $translations = array_merge(
            $translations,
            (array) json_decode(file_get_contents($output))
        );
    }
    file_put_contents(
        $output,
        json_encode($translations, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
    );
    $this->comment("Scanned and saved to " . $output);
})->describe('Scan missing translation strings');

Artisan::command('module:add {module_name}', function ($module_name) {
    // Check if files are exists.
    $basePath = "/liman/modules/$module_name";

    if (!is_dir($basePath) || !is_file($basePath . "/db.json")) {
        return $this->error("Modül okunamadı!");
    }

    //Check if module supported or not.
    $json = json_decode(file_get_contents($basePath . "/db.json"), true);
    if (getVersionCode() < intval(trim($json["minLimanSupported"]))) {
        return $this->error(
            "Bu modülü yüklemek için önce liman'ı güncellemelisiniz!"
        );
    }

    $flag = Module::where(["name" => $module_name])->exists();

    if (!$flag) {
        $module = Module::create(["name" => $module_name, "enabled" => true]);

        $notification = new AdminNotification([
            "title" => "Yeni Modül Eklendi",
            "type" => "new_module",
            "message" => "$module_name isminde bir modül sisteme eklendi.",
            "level" => 3,
        ]);
    } else {
        $notification = new AdminNotification([
            "title" => $module_name . " modülü güncellendi.",
            "type" => "new_module",
            "message" => "$module_name isminde bir modül güncellendi.",
            "level" => 3,
        ]);
    }

    $notification->save();
    $this->info("Modül başarıyla yüklendi.");
})->describe("New module add");

Artisan::command('module:remove {module_name}', function ($module_name) {
    $module = Module::where('name', $module_name)->first();

    if (!$module) {
        return $this->error("Modul bulunamadi!");
    }

    $flag = $module->delete();

    if ($flag) {
        $this->info("Modul basariyla silindi.");
    } else {
        $this->error("Modul silinemedi.$flag");
    }
})->describe("Module remove");

Artisan::command('register_liman', function () {
    Liman::updateOrCreate([
        "machine_id" => getLimanId()
    ],[
        "last_ip" => env("LIMAN_IP",trim(`hostname -I`)),
        "rsync_password" => base64_encode(str_random())
    ]);
})->describe("Module remove");

Artisan::command('update_settings', function () {
    updateSystemSettings();
})->describe("Update the system settings");

Artisan::command('receive_settings', function () {
    receiveSystemSettings();
})->describe("Receive the system settings");

Artisan::command('receive_settings', function () {
    receiveSystemSettings();
})->describe("Receive the system settings");

Artisan::command('sync', function () {
    receiveSystemSettings();

    $masterIp = env('LIMAN_MASTER_IP');

    if($masterIp == ""){
        $firstLiman = Liman::first();
        $masterIp = $firstLiman->last_ip;
    }

    $this->info("Dosyalar eşitleniyor, kaynak : " . $masterIp);

    shell_exec("rsync -Pav -e \"ssh -i /home/liman/.ssh/liman_priv -o 'StrictHostKeyChecking no'\" liman@" . $masterIp . ":/liman/extensions/ /liman/extensions/");
    shell_exec("rsync -Pav -e \"ssh -i /home/liman/.ssh/liman_priv -o 'StrictHostKeyChecking no'\" liman@" . $masterIp . ":/liman/keys/ /liman/keys/");
    shell_exec("rsync -Pav -e \"ssh -i /home/liman/.ssh/liman_priv -o 'StrictHostKeyChecking no'\" liman@" . $masterIp . ":/liman/modules/ /liman/modules/");
    
    $root = rootSystem();
    $extensions = Extension::all();
    foreach($extensions as $extension){
        $this->info($extension->name . " eklentisinin kullanıcısı oluşturuluyor, izinleri düzenleniyor.");
        $root->userAdd($extension->id);
        $root->fixExtensionPermissions($extension->id,$extension->name);
    }
    
    $dns = SystemSettings::where([
        "key" => "SYSTEM_DNS"
    ])->first();
    if($dns){
        $json = json_decode($dns->data);
        $root->dnsUpdate($json[0],$json[1],$json[2]);
    }

    $certificates = SystemSettings::where([
        "key" => "SYSTEM_CERTIFICATES"
    ])->first();
    if($certificates){
        $json = json_decode($certificates->data,true);
        foreach($json as $cert){
            if(is_file("/usr/local/share/ca-certificates/" . $cert["targetName"] . ".crt")){
                continue;
            }
            $root->addCertificate($cert["certificate"],$cert["targetName"]);
        }
    }
    
})->describe("Sync everything");