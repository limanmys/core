<?php

Route::middleware(["admin"])->group(function () {
    // List All Requests Route
    Route::get('/talepler', 'Permission\MainController@all')->name('request_list');

    // LimanRequest Details Route
    Route::get('/talep/{permission_id}', 'Permission\MainController@one')->name(
        'request_one'
    );

    // LimanRequest Update Route
    Route::post('/talep/guncelle', 'Permission\MainController@requestUpdate')->name(
        'request_update'
    );
});