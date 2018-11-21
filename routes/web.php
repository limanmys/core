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

//Route::get('/', function () {
//	if(Auth::check() == false){
//    	return redirect('/login');
//    }else{
//    	return view('welcome');
//    }
//});

Auth::routes();

Route::group(['middleware' => ['auth']], function () {
    Route::get('/', 'HomeController@index')->name('home');
    Route::get('/sunucular', 'ServerController@index')->name('servers');
    Route::group(['middleware' => ['parameters:server_id','server']], function () {
        Route::get('/sunucular/{server_id}', 'ServerController@one')->name('server_one');
        Route::post('/sunucu/ekle' , 'ServerController@add')->name('server_add')->middleware('parameters:username,password,ip_address,port');
        Route::post('/sunucu/sil', 'ServerController@remove')->name('server_remove')->middleware('');
        Route::post('/sunucu/calistir', 'ServerController@run')->name('server_run');
        Route::post('/sunucu/kontrol', 'ServerController@check')->name('server_check');
    });
    Route::get('/anahtarlar','SshController@index')->name('keys');
    Route::post('/anahtar/ekle','SshController@add')->name('key_add');

    Route::get('/kullanicilar','UserController@index')->name('users');

    Route::get('/betikler', 'ScriptController@index')->name('scripts');
    Route::get('/betik/ekle', 'ScriptController@add')->name('script_add');
    Route::get('/betik/{id}' , 'ScriptController@one')->name('script_one');
    Route::post('/betik/calistir', 'ServerController@runScript')->name('script_run');
    Route::post('/betik/yukle', 'ScriptController@upload')->name('script_upload');

    Route::get('/l/{feature}', 'FeatureController@index')->name('feature');
    Route::get('/l/{feature}/{city}', 'FeatureController@city')->name('feature_city');
    Route::get('/l/{feature}/{city}/{server}', 'FeatureController@server')->name('feature_server');

    Route::get('/ayarlar', 'SettingsController@index')->name('settings');
});