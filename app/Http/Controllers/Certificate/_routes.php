<?php

Route::post('/sunucu/sertifikaOnayi','Certificate\MainController@verifyCert')->name('verify_certificate')->middleware('admin');

Route::post('/sunucu/sertifikaSil','Certificate\MainController@removeCert')->name('remove_certificate')->middleware('admin');

Route::view('/ayarlar/sertifika','settings.certificate')->name('certificate_add_page')->middleware('admin');

Route::post('/ayarlar/sertifikaTalep','Certificate\MainController@requestCert')->name('certificate_request')->middleware('admin');