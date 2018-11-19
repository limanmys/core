<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
	if(Auth::check() == false){
    	return redirect('/login');
    }else{
    	return view('welcome');
    }
});

Auth::routes();

Route::group(['middleware' => ['auth']], function () {
    Route::get('/', 'HomeController@index')->name('home');
    Route::get('/sunucular', 'ServerController@index')->name('servers');
    Route::get('/sunucular/{id}', 'ServerController@one')->name('server_one');
    Route::post('/sunucu/ekle' , 'ServerController@add')->name('server_add');
    Route::post('/sunucu/sil', 'ServerController@remove')->name('server_remove');
    Route::post('/sunucu/calistir', 'ServerController@run')->name('server_run');

    Route::get('/anahtarlar','SshController@index')->name('keys');
    Route::post('/anahtar/ekle','SshController@add')->name('key_add');

    Route::get('/kullanicilar','UserController@index')->name('users');

    Route::get('/betikler', 'ScriptController@index')->name('scripts');
    Route::get('/betik/ekle', 'ScriptController@add')->name('script_add');
    Route::post('/betik/calistir', 'ServerController@runScript')->name('script_run');
    Route::get('/betik/{id}' , 'ScriptController@one')->name('script_one');
    Route::post('/betik/yukle', 'ScriptController@upload')->name('script_upload');
    Route::get('/l/{feature}', 'FeatureController@index');

});