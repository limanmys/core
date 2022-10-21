<?php

Route::middleware(['admin'])->group(function () {
    // Certificate Add View
    Route::view('/ayarlar/sertifika', 'settings.certificate')
        ->name('certificate_add_page');

    // Add Certificate
    Route::post('/sunucu/sertifikaOnayi', 'Certificate\MainController@verifyCert')
        ->name('verify_certificate');

    // Delete Certificate
    Route::post('/sunucu/sertifikaSil', 'Certificate\MainController@removeCert')
        ->name('remove_certificate');

    // Update Certificate
    Route::post(
        '/sunucu/sertifikaGuncelle',
        'Certificate\MainController@updateCert'
    )
        ->name('update_certificate');

    // Retrieve Certificate
    Route::post('/ayarlar/sertifikaTalep', 'Certificate\MainController@requestCert')
        ->name('certificate_request');

    Route::post('/ayarlar/sertifikaBilgi', 'Certificate\MainController@getCertificateInfo')
        ->name('certificate_info');

    Route::get('/ayarlar/sertifikaDetay', 'Certificate\MainController@one')
        ->name('certificate_one');
});
