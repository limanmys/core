<?php

use App\User;

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

Route::group(['middleware' => ['auth', 'google2fa']], function() {
    Route::post('/2fa', function () {
        return redirect(route('home'));
    })
        ->name('2fa');
    Route::get('/2fa/register', 'UserController@setGoogleSecret')
        ->name('registerGoogleAuth');
    Route::post('/2fa/setSecret', function() {
        User::find(auth()->user()->id)->update([
            'google2fa_secret' => request('_secret')
        ]);

        return redirect(route('home'));
    })
        ->name('set_google_secret');
});