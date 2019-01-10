<?php

// Extension' Server' Home Route

Route::get('/l/{extension_id}/{city}/{server_id}', 'Extension\OneController@server')->name('extension_server')->middleware('server');

// Extension' Server' Any Route Handler

Route::get('/l/{extension_id}/{city}/{server_id}/{unique_code}', 'Extension\OneController@route')->middleware(['server','script_parameters']);

// Extension Management Route

// Route::post('/extension/{unique_code}', 'ExtensionController@route')->name('extension_api')->middleware(['server', 'script_parameters']);

// Extension Page (City Select) Route

Route::get('/l/{extension_id}', 'Extension\MainController@all')->name('extension_id');

// Extension City Servers Route

Route::get('/l/{extension_id}/{city}', 'Extension\MainController@city')->name('extension_city');

// Extensions List Route

Route::get('/eklentiler', 'Extension\SettingsController@settings_all')->name('extensions_settings');

// Extension Details Route

Route::get('/eklentiler/{extension_id}', 'Extension\SettingsController@settings_one')->name('extension_one');

// Extension View Scripts

Route::post('/eklentiler/betikler', 'Extension\SettingsController@getScriptsOfView')->name('extension_page_scripts');

// Extension View Script Add

Route::post('/eklentiler/betikler/ekle', 'Extension\SettingsController@addScriptToView')->name('extension_page_script_add');

// Extension View Script Remove

Route::post('/eklentiler/betikler/sil', 'Extension\SettingsController@removeScriptFromView')->name('extension_page_script_remove');