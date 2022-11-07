<?php

Route::post('/captcha', 'Auth\LoginController@captcha')->name('captcha');
Route::get('/giris', 'Auth\LoginController@showLoginForm')->name('login');
Route::post('/giris', 'Auth\LoginController@login');
Route::post('/cikis', 'Auth\LogoutController@logout')
    ->name('logout')
    ->middleware('auth');
Route::get('/keycloak/auth', 'Auth\LoginController@redirectToKeycloak')
    ->name('keycloak-auth');
    Route::get('/keycloak/callback', 'Auth\LoginController@retrieveFromKeycloak')
    ->name('keycloak-callback');