<?php

Route::get('/widgetlar','Widgets\MainController@settings');

Route::post('/widget/ekle','Widgets\MainController@add')->name('widget_add');

Route::post('/deneme','Widgets\OneController@one')->name('widget_one');

Route::post('/widget/sil','Widgets\OneController@remove')->name('widget_remove');

Route::post('/widget/update','Widgets\OneController@update')->name('widget_update');