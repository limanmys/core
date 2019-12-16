<?php

// Certificate Add View
Route::view('/ayarlar/sertifika','settings.certificate')->name('certificate_add_page')->middleware('admin');

// Add Certificate
Route::post('/sunucu/sertifikaOnayi','Certificate\MainController@verifyCert')->name('verify_certificate')->middleware('admin');

// Delete Certificate
Route::post('/sunucu/sertifikaSil','Certificate\MainController@removeCert')->name('remove_certificate')->middleware('admin');

// Update Certificate
Route::post('/sunucu/sertifikaGuncelle','Certificate\MainController@updateCert')->name('update_certificate')->middleware('admin');

// Retrieve Certificate
Route::post('/ayarlar/sertifikaTalep','Certificate\MainController@requestCert')->name('certificate_request')->middleware('admin');

Route::get('/ayarlar/sertifikaDetay','Certificate\MainController@one')->name('certificate_one')->middleware('admin');