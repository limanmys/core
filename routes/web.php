<?php

// Auth Routes
require_once(app_path('Http/Controllers/Auth/_routes.php'));

Route::group(['middleware' => ['auth','permissions']],function () {

// Extension Routes

require_once(app_path('Http/Controllers/Extension/_routes.php'));

// Notification Routes

require_once(app_path('Http/Controllers/Notification/_routes.php'));

// Permission Routes

require_once(app_path('Http/Controllers/Permission/_routes.php'));

// Script Routes

require_once(app_path('Http/Controllers/Script/_routes.php'));

// Server Routes

require_once(app_path('Http/Controllers/Server/_routes.php'));

// Server Routes

require_once(app_path('Http/Controllers/Settings/_routes.php'));

// Widgets Routes

require_once(app_path('Http/Controllers/Widgets/_routes.php'));

// Change the language
Route::get('/locale', 'HomeController@setLocale')->name('set_locale');

// Change the language
Route::post('/theme', 'HomeController@setTheme')->name('set_theme');

// Set Collapse

Route::post('/collapse','HomeController@collapse')->name('set_collapse');

// Home Route

Route::get('/', 'HomeController@index')->name('home');

// SSH Key List Route

Route::get('/kasa', 'KeyController@index')->name('keys');

// SSH Key Add Route

Route::post('/anahtar/ekle', 'KeyController@add')->name('key_add');

Route::post('/anahtar/sil', 'KeyController@delete')->name('key_delete');

// User Details Route

Route::get('/kullanici/{user_id}', 'UserController@one')->name('user');

// My Requests Route

Route::get('/taleplerim', 'HomeController@all')->name('request_permission');

// Send LimanRequest Route

Route::post('/talep', 'HomeController@request')->name('request_send');

// Search Page

Route::post('/arama/','SearchController@index')->name('search');

// Log View Route
Route::view('/logs/{log_id}','logs.one');

// User Add
Route::post('/kullanici/ekle','UserController@add')->name('user_add')->middleware('admin');

// User Remove
Route::post('/kullanici/sil','UserController@remove')->name('user_remove')->middleware('admin');

// User Remove
Route::post('/kullanici/parola/sifirla','UserController@passwordReset')->name('user_password_reset')->middleware('admin');

Route::view('/logs/{log_id}','logs.one');

Route::view('/profil','user.self')->name('my_profile');

Route::post('/profil','UserController@selfUpdate')->name('profile_update');

Route::post('/user/update','UserController@adminUpdate')->name('update_user')->middleware('admin');

Route::post('/user/setting/delete','UserController@removeSetting')->name('user_setting_remove');
});

Route::post('/lmn/private/extensionApi','Extension\OneController@internalExtensionApi');

Route::post('/lmn/private/runCommandApi','Extension\OneController@internalRunCommandApi');

Route::post('/lmn/private/putFileApi','Extension\OneController@internalPutFileApi');

Route::post('/lmn/private/getFileApi','Extension\OneController@internalGetFileApi');
