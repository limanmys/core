<?php

// Script Add View Route

Route::get('/betik/ekle', 'Script\MainController@add')->name('script_add');

// Script Add Route

Route::post('/betik/ekle', 'Script\MainController@create')->name('script_create');

// Script Details Route

Route::get('/betik/detay/{script_id}', 'Script\MainController@one')->name('script_one');

// Script Upload Route

Route::post('/betik/yukle', 'Script\MainController@upload')->name('script_upload');

// Script List Route

Route::get('/betikler', 'Script\MainController@index')->name('scripts');

// Script Download Route
Route::get('/indir/betik/{script_id}','Script\MainController@download')->name('script_download');
