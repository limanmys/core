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
    Route::post('/sunucu/ekle' , 'ServerController@add')->name('server_add')->middleware('parameters:username,password,ip_address,port');

    Route::group(['middleware' => ['server']], function () {

        Route::get('/l/{feature}/{city}/{server_id}', 'ExtensionsController@server')->name('feature_server');
        Route::get('/sunucular/{server_id}', 'ServerController@one')->name('server_one');
        Route::post('/sunucu/sil', 'ServerController@remove')->name('server_remove')->middleware('parameters:server_id');
        Route::post('/sunucu/calistir', 'ServerController@run')->name('server_run');
        Route::post('/sunucu/kontrol', 'ServerController@check')->name('server_check')->middleware('parameters:feature,server_id');
        Route::post('/extension/{extension_id}/','ServerController@generatePage')->name('extension_api')->middleware('script_parameters');
    });

    Route::get('/anahtarlar','SshController@index')->name('keys');
    Route::post('/anahtar/ekle','SshController@add')->name('key_add');

    Route::get('/kullanicilar','UserController@index')->name('users');

    Route::get('/betikler', 'ScriptController@index')->name('scripts');
    Route::get('/betik/ekle', 'ScriptController@add')->name('script_add');
    Route::get('/betik/{id}' , 'ScriptController@one')->name('script_one');
    Route::post('/betik/calistir', 'ServerController@runScript')->name('script_run');
    Route::post('/betik/yukle', 'ScriptController@upload')->name('script_upload');

    Route::get('/l/{feature}', 'ExtensionsController@index')->name('feature');
    Route::get('/l/{feature}/{city}', 'ExtensionsController@city')->name('feature_city');

    Route::get('/ayarlar', 'SettingsController@index')->name('settings');

    Route::get('/eklentiler' , 'ExtensionsController@settings')->name('extensions_settings');
    Route::get('/eklentiler/{id}','ExtensionsController@one')->name('extension_one');
});