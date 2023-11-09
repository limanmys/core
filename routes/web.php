<?php

use Illuminate\Support\Facades\Route;

Route::any("/", function () {
    return redirect("/api");
})->name('home');

// HA Routes
require_once app_path('Http/Controllers/HASync/_routes.php');

Route::group(['middleware' => ['auth', 'permissions']], function () {
    // Internal Sandbox Routes
    require_once app_path('Http/Controllers/Extension/Sandbox/_routes.php');
});

Route::any('/upload/{any?}', function () {
    $server = app('tus-server');
    $extension_id = request()->headers->get('extension-id');
    $extension = \App\Models\Extension::find($extension_id);
    if ($extension) {
        $path = '/liman/extensions/' . strtolower((string) $extension->name);
    } else {
        $path = storage_path();
    }

    if (!file_exists($path . '/uploads')) {
        mkdir($path . '/uploads');
        if ($extension) {
            rootSystem()->fixExtensionPermissions($extension_id, $extension->name);
        } else {
            rootSystem()->fixExtensionPermissions('liman', 'liman');
        }
    }
    $server->setUploadDir($path . '/uploads');
    $response = $server->serve();
    return $response->send();
})
    ->where('any', '.*')
    ->middleware(['upload_token_check']);

Route::post('/upload_info', function () {
    request()->validate([
        'key' => 'required',
    ]);
    $key = request('key');
    $server = app('tus-server');
    $info = $server->getCache()->get($key);
    $extension_id = request('extension_id');
    $extension = \App\Models\Extension::find($extension_id);
    if ($extension_id) {
        $extension_path = explode('/uploads/', (string) $info['file_path'], 2)[0];
        $info['file_path'] = str_replace(
            $extension_path,
            '',
            (string) $info['file_path']
        );
        rootSystem()->fixExtensionPermissions($extension_id, $extension->name);
    }

    return $info;
})->middleware(['upload_token_check']);

Route::get(
    '/eklenti/{extension_id}/public/{any}',
    'API\ExtensionController@publicFolder'
)->where('any', '.+')->name('extension_public_folder');

