<?php

// Auth Routes
require_once app_path('Http/Controllers/Auth/_routes.php');

Route::group(['middleware' => ['auth', 'permissions']], function () {
    // Extension Routes

    require_once app_path('Http/Controllers/Extension/_routes.php');

    // Notification Routes

    require_once app_path('Http/Controllers/Notification/_routes.php');

    // Permission Routes

    require_once app_path('Http/Controllers/Permission/_routes.php');

    // Server Routes

    require_once app_path('Http/Controllers/Server/_routes.php');

    // Certificate Routes

    require_once app_path('Http/Controllers/Certificate/_routes.php');

    // Settings Routes

    require_once app_path('Http/Controllers/Settings/_routes.php');

    // Market Routes

    require_once app_path('Http/Controllers/Market/__routes.php');

    // Wizard Routes

    require_once app_path('Http/Controllers/Wizard/_routes.php');

    // Modules Routes

    require_once app_path('Http/Controllers/Module/_routes.php');

    // Role Routes

    require_once app_path('Http/Controllers/Roles/_routes.php');

    // Cron Mail Routes
    require_once app_path('Http/Controllers/CronMail/__routes.php');

    // Internal Sandbox Routes

    require_once app_path('Http/Controllers/Extension/Sandbox/_routes.php');

    // Change the language
    Route::get('/locale', 'HomeController@setLocale')->name('set_locale');

    // Home Route

    Route::get('/', 'HomeController@index')->name('home');

    Route::post('/', 'HomeController@getLimanStats')
        ->name('liman_stats')
        ->middleware('admin');

    Route::post('/online_servers', 'HomeController@getServerStatus')
        ->name('online_servers')
        ->middleware('admin');

    // Vault Route

    Route::get('/kasa', 'UserController@userKeyList')->name('keys');

    Route::get('/takip', 'ServerMonitorController@list')->name('monitor_list');

    Route::post('/takip/sil', 'ServerMonitorController@remove')->name('monitor_remove');

    Route::post('/takip/ekle', 'ServerMonitorController@add')->name('monitor_add');

    Route::post('/takip/yenile', 'ServerMonitorController@refresh')->name('monitor_refresh');

    // Add Key Route
    Route::post('/kasa/ekle', 'UserController@addKey')->name('key_add');

    // My Requests Route

    Route::get('/taleplerim', 'HomeController@all')->name('request_permission');

    // Send LimanRequest Route

    Route::post('/talep', 'HomeController@request')->name('request_send');

    // User Add
    Route::post('/kullanici/ekle', 'UserController@add')
        ->name('user_add')
        ->middleware('admin');

    // User Remove
    Route::post('/kullanici/sil', 'UserController@remove')
        ->name('user_remove')
        ->middleware('admin');

    // User Remove
    Route::post('/kullanici/parola/sifirla', 'UserController@passwordReset')
        ->name('user_password_reset')
        ->middleware('admin');

    Route::view('/profil', 'user.self')->name('my_profile');

    Route::get('/profil/anahtarlarim', 'UserController@myAccessTokens')->name(
        'my_access_tokens'
    );

    Route::post(
        '/profil/anahtarlarim/ekle',
        'UserController@createAccessToken'
    )->name('create_access_token');

    Route::post(
        '/profil/anahtarlarim/sil',
        'UserController@revokeAccessToken'
    )->name('revoke_access_token');

    Route::post('/profil', 'UserController@selfUpdate')->name('profile_update');

    Route::post('/user/update', 'UserController@adminUpdate')
        ->name('update_user')
        ->middleware('admin');

    Route::post('/user/setting/delete', 'UserController@removeSetting')->name(
        'user_setting_remove'
    );

    Route::post('/user/setting/update', 'UserController@updateSetting')->name(
        'user_setting_update'
    );

    Route::get('/liman_arama', 'SearchController@search')->name('search');
});

Route::any('/upload/{any?}', function () {
    $server = app('tus-server');
    $extension_id = request('extension_id');
    $extension = \App\Models\Extension::find($extension_id);
    if ($extension) {
        $path = '/liman/extensions/'.strtolower((string) $extension->name);
    } else {
        $path = storage_path();
    }
    if (! file_exists($path.'/uploads')) {
        mkdir($path.'/uploads');
        if ($extension) {
            rootSystem()->fixExtensionPermissions($extension_id, $extension->name);
        } else {
            rootSystem()->fixExtensionPermissions('liman', 'liman');
        }
    }
    $server->setUploadDir($path.'/uploads');
    $response = $server->serve();

    return $response->send();
})
    ->where('any', '.*')
    ->middleware(['auth', 'permissions']);

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
})->middleware(['auth', 'permissions']);

registerModuleRoutes();

Route::get('/bildirimYolla', 'Notification\ExternalNotificationController@accept');
