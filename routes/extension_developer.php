<?php

Route::post('/eklenti/yeni', 'Extension\MainController@newExtension')->name('extension_new')->middleware('admin');

Route::get('/eklentiler/{extension_id}/{page_name}', 'Extension\OneController@page')->middleware('admin')->name('extension_page_edit_view');

Route::post('/ayar/eklenti/guncelle', 'Extension\SettingsController@update')->middleware('admin')->name('extension_settings_update');

Route::post('/ayar/eklenti/ekle', 'Extension\SettingsController@add')->middleware('admin')->name('extension_settings_add');

Route::post('/ayar/eklenti/sil', 'Extension\SettingsController@remove')->middleware('admin')->name('extension_settings_remove');

Route::post('/ayar/eklenti/kod', 'Extension\OneController@updateCode')->middleware('admin')->name('extension_code_update');

Route::post('/ayar/eklenti/yeni/sayfa', 'Extension\MainController@newExtensionPage')->middleware('admin')->name('extension_new_page');

// Extension Download Page
Route::get('/indir/eklenti/{extension_id}', 'Extension\MainController@download')->name('extension_download');

Route::get('/indir/eklenti_deb/{extension_id}', 'Extension\MainController@download_deb')->name('extension_deb_download');
