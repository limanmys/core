<?php

Route::get('/giris', 'Auth\LoginController@showLoginForm')->name('login');
Route::post('/giris', 'Auth\LoginController@login');
Route::post('/cikis', 'Auth\LogoutController@logout')
    ->name('logout')
    ->middleware('auth');
