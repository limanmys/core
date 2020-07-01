<?php

use App\User;
use App\Module;
use App\AdminNotification;
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
            $translations = array_merge(
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
    if (!is_dir("/liman/modules/$module_name")) {
        return $this->error("Modul klasoru bulunamadi!");
    }

    if (Module::where(["name" => $module_name])->exists()) {
        return $this->error("Boyle bir modul zaten var!");
    }
    $module = Module::create(["name" => $module_name, "enabled" => true]);

    $notification = new AdminNotification([
        "title" => "Yeni Modül Eklendi",
        "type" => "new_module",
        "message" => "$module->name isminde bir modül sisteme eklendi.",
        "level" => 3,
    ]);
    $notification->save();
    $this->info("Modul basariyla yuklendi.");
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
