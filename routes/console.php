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
        $module = Module::create(['name' => $module_name, 'enabled' => true]);

        $notification = new AdminNotification([
            'title' => 'Yeni Modül Eklendi',
            'type' => 'new_module',
            'message' => "$module_name isminde bir modül sisteme eklendi.",
            'level' => 3,
        ]);
    } else {
        Module::where(['name' => $module_name])->first()->touch();

        $notification = new AdminNotification([
            'title' => $module_name.' modülü güncellendi.',
            'type' => 'new_module',
            'message' => "$module_name isminde bir modül güncellendi.",
            'level' => 3,
        ]);
    }

    $notification->save();
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
        'machine_id' => getLimanId(),
    ], [
        'last_ip' => env('LIMAN_IP', trim((string) `hostname -I`)),
    ]);
})->describe('Register liman');

Artisan::command('update_settings', function () {
    updateSystemSettings();
})->describe('Update the system settings');

Artisan::command('receive_settings', function () {
    receiveSystemSettings();
})->describe('Receive the system settings');

Artisan::command('sync_core', function () {
    if (trim((string) `id -u`) != '0') {
        $this->error('Bu komutu root olarak çalışmalısınız!');

        return;
    }

    receiveSystemSettings();

    `
        systemctl restart nginx;
        systemctl restart liman-render;
        systemctl restart liman-system;
        systemctl restart liman-socket;
    `;
})->describe('Sync core files.');

Artisan::command('sync_safe', function () {
    syncFiles();
})->describe('Sync safe files without restarting');
