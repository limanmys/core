<?php

// Settings Route

Route::get('/ayarlar', 'Settings\MainController@index')->name('settings')->middleware('admin');

Route::get('/ayarlar/{user}','Settings\MainController@one')->name('settings_one')->middleware('admin');

Route::post('/ayarlar/liste','Settings\MainController@getList')->name('settings_get_list')->middleware('admin');

Route::post('/ayar/yetki/ekle','Settings\MainController@addList')->name('settings_add_to_list')->middleware('admin');

Route::post('/ayar/yetki/sil','Settings\MainController@removeFromList')->name('settings_remove_from_list')->middleware('admin');

Route::view('/ayar/sunucu','settings.server')->middleware('admin')->name('settings_server');

Route::post('/ayar/eklenti/fonksiyonlar','Settings\MainController@getExtensionFunctions')->middleware('admin')->name('extension_function_list');

Route::post('/ayar/eklenti/fonksiyonlar/ekle','Settings\MainController@addFunctionPermissions')->middleware('admin')->name('extension_function_add');

Route::post('/ayar/eklenti/fonksiyonlar/sil','Settings\MainController@removeFunctionPermissions')->middleware('admin')->name('extension_function_remove');

Route::post('/ayarlar/saglik','Settings\MainController@health')->middleware('admin')->name('health_check');

Route::view('/sifreDegistir','user.password')->middleware('auth')->name('password_change');

Route::post('/sifreDegistir','UserController@forcePasswordChange')->middleware('auth')->name('password_change_save');