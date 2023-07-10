<?php

use App\Models\AdminNotification;
use App\Models\Liman;
use App\Models\Module;
use Illuminate\Support\Facades\Artisan;

Artisan::command('scan:translations', function () {
    if (env('EXTENSION_DEVELOPER_MODE') != true) {
        return $this->error(
            'You need to open extension developer mode for use this function.'
        );
    }
    $extension_path = '/liman/extensions/';
    $extensions = glob($extension_path.'/*', GLOB_ONLYDIR);
    $this->info('Started to scanning extension folders.');
    foreach ($extensions as $extension) {
        $this->comment('Scanning: '.$extension);
        $output = "$extension/lang/en.json";
        $translations = scanTranslations($extension);
        if (! is_dir(dirname($output))) {
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
        $this->info('Scanned and saved to '.$output);
    }
    $this->info('Finished scanning extension folders.');

    $this->info('Started to scanning server files.');
    $server_path = '/liman/server';
    $this->comment('Scanning: '.$server_path);
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
    $this->comment('Scanned and saved to '.$output);
})->describe('Scan missing translation strings');

Artisan::command('module:add {module_name}', function ($module_name) {
    // Check if files are exists.
    $basePath = "/liman/modules/$module_name";

    if (! is_dir($basePath) || ! is_file($basePath.'/db.json')) {
        return $this->error('Modül okunamadı!');
    }

    //Check if module supported or not.
    $json = json_decode(file_get_contents($basePath.'/db.json'), true);
    if (getVersionCode() < intval(trim((string) $json['minLimanSupported']))) {
        return $this->error(
            "Bu modülü yüklemek için önce liman'ı güncellemelisiniz!"
        );
    }

    $flag = Module::where(['name' => $module_name])->exists();

    if (! $flag) {
        Module::create(['name' => $module_name, 'enabled' => true]);
    } else {
        Module::where(['name' => $module_name])->first()->touch();
    }

    $this->info('Modül başarıyla yüklendi.');
})->describe('New module add');

Artisan::command('module:remove {module_name}', function ($module_name) {
    $module = Module::where('name', $module_name)->first();

    if (! $module) {
        return $this->error('Modul bulunamadi!');
    }

    $flag = $module->delete();

    if ($flag) {
        $this->info('Modul basariyla silindi.');
    } else {
        $this->error("Modul silinemedi.$flag");
    }
})->describe('Module remove');

Artisan::command('register_liman', function () {
    Liman::updateOrCreate([
        'last_ip' => env('LIMAN_IP', trim((string) `hostname -I | cut -d' ' -f1 | xargs`)),
    ], [
        'last_ip' => env('LIMAN_IP', trim((string) `hostname -I | cut -d' ' -f1 | xargs`)),
    ]);
})->describe('Register liman');
