<?php

Route::get('/widgetlar','Widgets\MainController@settings')->name('widgets');

Route::post('/widget/ekle','Widgets\MainController@add')->name('widget_add');

Route::view('/widget/ekle','widgets.add')->name('widget_add_page');

Route::post('/widget','Widgets\OneController@one')->name('widget_one');

Route::post('/widget/sil','Widgets\OneController@remove')->name('widget_remove');

Route::post('/widget/update','Widgets\OneController@update')->name('widget_update');

Route::post('/widget/extensions','Widgets\OneController@extensions')->name('widget_get_extensions');

Route::post('/widget/list','Widgets\OneController@widgetList')->name('widget_list');