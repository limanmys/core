<?php

use Illuminate\Support\Facades\Route;

Route::any('/', function () {
    return redirect('/api');
})->name('home');

// HA Routes
require_once app_path('Http/Controllers/HASync/_routes.php');

// Internal Sandbox Routes
require_once app_path('Http/Controllers/Extension/Sandbox/_routes.php');

Route::any('/upload/{any?}', function () {
    $extension = request()->attributes->get('extension');
    $path = '/liman/extensions/'.strtolower((string) $extension->name);

    if (! file_exists($path.'/uploads')) {
        mkdir($path.'/uploads');
        rootSystem()->fixExtensionPermissions($extension->id, $extension->name);
    }
    $server = app('tus-server');
    $server->setUploadDir($path.'/uploads');
    $response = $server->serve();

    return $response->send();
})
    ->where('any', '.*')
    ->middleware(['extension.access', 'throttle:upload']);

Route::post('/upload_info', function () {
    request()->validate([
        'key' => 'required',
    ]);
    $key = request('key');
    $server = app('tus-server');
    $info = $server->getCache()->get($key);

    if (! $info) {
        return response()->json([
            'message' => 'Dosya bulunamadı.',
        ], 404);
    }

    $extension = request()->attributes->get('extension');

    rootSystem()->fixExtensionPermissions($extension->id, $extension->name);

    return [
        'name' => $info['name'] ?? basename((string) ($info['file_path'] ?? '')),
        'size' => $info['size'] ?? 0,
        'offset' => $info['offset'] ?? 0,
        'file_path' => '/uploads/'.basename((string) ($info['file_path'] ?? '')),
    ];
})->middleware(['extension.access', 'throttle:upload']);

Route::get(
    '/eklenti/{extension_id}/public/{any}',
    'API\ExtensionController@publicFolder'
)->where('any', '.+')->name('extension_public_folder');
