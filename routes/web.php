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


// Change the language
Route::post('/locale', 'HomeController@setLocale')->name('set_locale');

// Change the language
Route::post('/theme', 'HomeController@setTheme')->name('set_theme');

// Home Route

Route::get('/', 'HomeController@index')->name('home');

// SSH Key List Route

Route::get('/anahtarlar', 'SshController@index')->name('keys');

// SSH Key Add Route

Route::post('/anahtar/ekle', 'SshController@add')->name('key_add');

// User Details Route

Route::get('/kullanici/{user_id}', 'UserController@one')->name('user');


// My Requests Route

Route::get('/taleplerim', 'HomeController@all')->name('request_permission');

// Send LimanRequest Route

Route::post('/talep', 'HomeController@request')->name('request_send');


// Search Page

Route::post('/arama/','SearchController@index')->name('search');
});