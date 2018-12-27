<?php

Route::get('login', 'Auth\LoginController@showLoginForm')->name('login');
Route::post('login', 'Auth\LoginController@login');
Route::post('/logout','AuthController@logout')->name('logout');

Route::get('register', 'Auth\RegisterController@showRegistrationForm')->name('register');
Route::post('register', 'Auth\RegisterController@register');


// Change the language
Route::post('/locale', 'HomeController@setLocale')->name('set_locale');

Route::group(['middleware' => ['auth','permissions']],function () {

// Home Route

    Route::get('/', 'HomeController@index')->name('home');

// Servers Route

    Route::get('/sunucular', 'ServerController@index')->name('servers');

// Add Server Route

    Route::post('/sunucu/ekle', 'Server\AddController@main')->name('server_add')->middleware('parameters:ip_address,control_port,type,city');

// Server Status Route (Telnet)

    Route::post('/api/status', 'ServerController@isAlive')->middleware('parameters:ip,port');

// Middleware to convert server_id to server object.

    Route::group(['middleware' => ['server']], function () {

        // Extension' Server' Home Route

        Route::get('/l/{extension_id}/{city}/{server_id}', 'ExtensionController@server')->name('extension_server');

        // Extension' Server' Any Route Handler

        Route::get('/l/{extension_id}/{city}/{server_id}/{unique_code}', 'ExtensionController@route')->middleware('script_parameters');

        // Single Server Details Route

        Route::get('/sunucular/{server_id}', 'Server\OneController@main')->name('server_one');

        // Remove Server Route

        Route::post('/sunucu/sil', 'ServerController@remove')->name('server_remove')->middleware('parameters:server_id');

        // Server Update Route

        Route::post('/sunucu/guncelle', 'ServerController@update')->name('server_update')->middleware('parameters:server_id,name,control_port');

        // Server Command Route

        Route::post('/sunucu/calistir', 'ServerController@run')->name('server_run');

        // Server' Service Status Route

        Route::post('/sunucu/kontrol', 'ServerController@check')->name('server_check')->middleware('parameters:extension_id,server_id');

        // Server Network Update

        Route::post('/sunucu/network', 'ServerController@network')->name('server_network')->middleware('parameters:ip,cidr,gateway,interface,password');

        // Server Hostname Update

        Route::post('/sunucu/hostname', 'ServerController@hostname')->name('server_hostname')->middleware('parameters:hostname');

        // Server Service Run,Stop,Enable,Disable Route

        Route::post('/sunucu/servis', 'ServerController@service')->name('server_service')->middleware('parameters:extension_id,action');

        // Server Extension Installation Route

        Route::post('/sunucu/eklenti', 'ServerController@enableExtension')->name('server_extension');
    });

// SSH Key List Route

    Route::get('/anahtarlar', 'SshController@index')->name('keys');

// SSH Key Add Route

    Route::post('/anahtar/ekle', 'SshController@add')->name('key_add');

// User Details Route

    Route::get('/kullanici/{user_id}', 'UserController@one')->name('user');

// Script List Route

    Route::get('/betikler', 'ScriptController@index')->name('scripts');

// Script Add View Route

    Route::get('/betik/ekle', 'ScriptController@add')->name('script_add');

// Script Add Route

    Route::post('/betik/ekle', 'ScriptController@create')->name('script_create');

// Script Details Route

    Route::get('/betik/{script_id}', 'ScriptController@one')->name('script_one');

// Script Run Route

    Route::post('/betik/calistir', 'ServerController@runScript')->name('script_run');

// Script Upload Route

    Route::post('/betik/yukle', 'ScriptController@upload')->name('script_upload');

// Extension Page (City Select) Route

    Route::get('/l/{extension_id}', 'ExtensionController@index')->name('extension_id');

// Extension City Servers Route

    Route::get('/l/{extension_id}/{city}', 'ExtensionController@city')->name('extension_city');

// Settings Route

    Route::get('/ayarlar', 'SettingsController@index')->name('settings');

// Extensions List Route

    Route::get('/eklentiler', 'ExtensionController@settings')->name('extensions_settings');

// Extension Details Route

    Route::get('/eklentiler/{extension_id}', 'ExtensionController@one')->name('extension_one');

// Extension Management Route

    Route::post('/extension/{unique_code}', 'ExtensionController@route')->name('extension_api')->middleware(['server', 'script_parameters']);

// My Requests Route

    Route::get('/taleplerim', 'HomeController@all')->name('request_permission');

// Send LimanRequest Route

    Route::post('/talep', 'HomeController@request')->name('request_send');

// List All Requests Route

    Route::get('/talepler', 'PermissionController@all')->name('request_list');

// LimanRequest Details Route

    Route::get('/talep/{permission_id}', 'PermissionController@one')->name('request_one');


// Search Page

    Route::post('/arama/','SearchController@index')->name('search');

// Notifications List

    Route::post('/bildirimler','NotificationController@check')->name('user_notifications');
// Notification Read Route

    Route::post('/bildirim/oku','NotificationController@read')->name('notification_read');

//  Extension View Scripts

    Route::post('/eklentiler/betikler', 'ExtensionController@getScriptsOfView')->name('extension_page_scripts');


});