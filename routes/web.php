<?php

Auth::routes();

Route::post('/locale','HomeController@setLocale')->name('set_locale');

Route::group(['middleware' => ['auth','permissions']], function () {

    Route::get('/', 'HomeController@index')->name('home');
    Route::get('/sunucular', 'ServerController@index')->name('servers');
    Route::post('/sunucu/ekle' , 'ServerController@add')->name('server_add')->middleware('parameters:username,password,ip_address,port');
    Route::post('/api/status', 'ServerController@isAlive')->middleware('parameters:ip,port');

    Route::group(['middleware' => ['server']], function () {
        Route::get('/l/{extension_id}/{city}/{server_id}', 'ExtensionController@server')->name('extension_server');
        Route::get('/l/{extension_id}/{city}/{server_id}/{unique_code}','ExtensionController@route')->middleware('script_parameters');
        Route::get('/sunucular/{server_id}', 'ServerController@one')->name('server_one');
        Route::post('/sunucu/sil', 'ServerController@remove')->name('server_remove')->middleware('parameters:server_id');
        Route::post('/sunucu/calistir', 'ServerController@run')->name('server_run');
        Route::post('/sunucu/kontrol', 'ServerController@check')->name('server_check')->middleware('parameters:extension_id,server_id');
        Route::post('/sunucu/network', 'ServerController@network')->name('server_network')->middleware('parameters:ip,cidr,gateway,interface,password');
        Route::post('/sunucu/hostname', 'ServerController@hostname')->name('server_hostname')->middleware('parameters:hostname');
        Route::post('/sunucu/servis', 'ServerController@service')->name('server_service')->middleware('parameters:extension_id,action');
        Route::post('/sunucu/eklenti', 'ServerController@enableExtension')->name('server_extension');
        Route::post('/extension/{extension_id}/','ExtensionController@route')->name('extension_api')->middleware('script_parameters');
    });

    Route::get('/anahtarlar','SshController@index')->name('keys');
    Route::post('/anahtar/ekle','SshController@add')->name('key_add');

    Route::get('/kullanici/{user_id}','UserController@one')->name('user');

    Route::get('/betikler', 'ScriptController@index')->name('scripts');
    Route::get('/betik/ekle', 'ScriptController@add')->name('script_add');
    Route::post('/betik/ekle', 'ScriptController@create')->name('script_create');
    Route::get('/betik/{script_id}' , 'ScriptController@one')->name('script_one');
    Route::post('/betik/calistir', 'ServerController@runScript')->name('script_run');
    Route::post('/betik/yukle', 'ScriptController@upload')->name('script_upload');

    Route::get('/l/{extension_id}', 'ExtensionController@index')->name('extension_id');
    Route::get('/l/{extension_id}/{city}', 'ExtensionController@city')->name('extension_city');

    Route::get('/ayarlar', 'SettingsController@index')->name('settings');

    Route::get('/eklentiler' , 'ExtensionController@settings')->name('extensions_settings');
    Route::get('/eklentiler/{extension_id}','ExtensionController@one')->name('extension_one');

    Route::get('/talep','HomeController@all')->name('request_permission');
    Route::post('/talep','HomeController@request')->name('request_send');
});