<?php

use App\Models\Liman;
use App\Models\Module;
use Illuminate\Support\Facades\Artisan;

Artisan::command('scan:translations', function () {
    if (! env('EXTENSION_DEVELOPER_MODE', false)) {
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

Artisan::command('register_liman', function () {
    Liman::updateOrCreate([
        'last_ip' => env('LIMAN_IP', trim((string) `hostname -I | cut -d' ' -f1 | xargs`)),
    ], [
        'last_ip' => env('LIMAN_IP', trim((string) `hostname -I | cut -d' ' -f1 | xargs`)),
    ]);
})->describe('Register liman');
