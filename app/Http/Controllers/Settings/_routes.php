<?php

// Settings Route

Route::get('/ayarlar', 'Settings\MainController@index')->name('settings')->middleware('admin');

Route::get('/ayarlar/{user_id}','Settings\MainController@one')->name('settings_one')->middleware('admin');

Route::post('/ayarlar/liste','Settings\MainController@getList')->name('settings_get_list')->middleware('admin');

Route::post('/ayar/yetki/ekle','Settings\MainController@addList')->name('settings_add_to_list')->middleware('admin');

Route::post('/ayar/yetki/sil','Settings\MainController@removeFromList')->name('settings_remove_from_list')->middleware('admin');

Route::view('/ayar/sunucu','settings.server')->middleware('admin')->name('settings_server');