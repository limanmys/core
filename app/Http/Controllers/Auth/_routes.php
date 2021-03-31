<?php

Route::post('/captcha', 'Auth\LoginController@captcha')->name('captcha');
Route::get('/giris', 'Auth\LoginController@showLoginForm')->name('login');
Route::post('/giris', 'Auth\LoginController@login');
Route::post('/cikis', 'Auth\LogoutController@logout')
    ->name('logout')
    ->middleware('auth');
