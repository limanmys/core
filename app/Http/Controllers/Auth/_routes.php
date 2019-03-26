<?php

Route::get('/giris', 'Auth\LoginController@showLoginForm')->name('login');
Route::post('/giris', 'Auth\LoginController@login');
Route::post('/cikis','Auth\LogoutController@logout')->name('logout');
<<<<<<< HEAD
=======

Route::get('register', 'Auth\RegisterController@showRegistrationForm')->name('register');
Route::post('register', 'Auth\RegisterController@register');
>>>>>>> origin/master
