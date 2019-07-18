<?php

// Extension' Server' Home Route
Route::get('/l/{extension_id}/{city}/{server_id}', 'Extension\OneController@renderView')->name('extension_server')->middleware('server');

// Extension' Server' Any Route Handler
Route::get('/l/{extension_id}/{city}/{server_id}/{unique_code}', 'Extension\OneController@renderView')->middleware(['server', 'script_parameters'])->name('extension_server_route');

// Extension Management Route
Route::post('/extension/run/{unique_code}', 'Extension\OneController@route')->name('extension_api')->middleware(['server', 'script_parameters']);

// Extension Page (City Select) Route
Route::get('/l/{extension_id}', 'Extension\MainController@allServers')->name('extension_map');

// Extension City Servers Route
Route::view('/l/{extension_id}/{city}', 'extension_pages.city')->name('extension_city');

// Extensions List Route
Route::get('/eklentiler', 'Extension\SettingsController@settings_all')->name('extensions_settings')->middleware('admin');

Route::post('/eklentiler_api', 'Extension\SettingsController@allServersApi')->name('extensions_api');

// Extension Details Route
Route::get('/eklentiler/{extension_id}', 'Extension\SettingsController@settings_one')->name('extension_one');

// Extension View Scripts
Route::post('/eklentiler/betikler', 'Extension\SettingsController@getScriptsOfView')->name('extension_page_scripts');

// Extension View Script Add
Route::post('/eklentiler/betikler/ekle', 'Extension\SettingsController@addScriptToView')->name('extension_page_script_add');

// Extension View Script Remove
Route::post('/eklentiler/betikler/sil', 'Extension\SettingsController@removeScriptFromView')->name('extension_page_script_remove');

// Extension Function Api
Route::post('/eklenti/{extension_id}/{function_name}', 'Extension\OneController@runFunction')->name('extension_function_api')->middleware('server');

// Extension Server Setting Page
Route::get('/ayarlar/{extension_id}/{server_id}', 'Extension\OneController@serverSettingsPage')->name('extension_server_settings_page')->middleware('server');

// Extension Server Settings
Route::post('/ayarlar/{extension_id}/{server_id}', 'Extension\OneController@serverSettings')->name('extension_server_settings')->middleware('server');

// Extension Upload Page
Route::post('/yukle/eklenti/', 'Extension\MainController@upload')->name('extension_upload');

// Extension Remove Page
Route::post('/eklenti/sil', 'Extension\OneController@remove')->name('extension_remove')->middleware('admin');

Route::post('/eklenti/update_ext_orders', 'Extension\MainController@updateExtOrders')->name('update_ext_orders')->middleware('admin');