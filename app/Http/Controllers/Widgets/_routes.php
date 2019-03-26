<?php

Route::get('/widgetlar','Widgets\MainController@settings')->name('widgets')->middleware('admin');

Route::post('/widget/ekle','Widgets\MainController@add')->name('widget_add')->middleware('admin');

Route::view('/widget/ekle','widgets.add')->name('widget_add_page')->middleware('admin');

Route::post('/widget','Widgets\OneController@one')->name('widget_one');

Route::post('/widget/sil','Widgets\OneController@remove')->name('widget_remove')->middleware('admin');

Route::post('/widget/update','Widgets\OneController@update')->name('widget_update')->middleware('admin');

Route::post('/widget/extensions','Widgets\OneController@extensions')->name('widget_get_extensions')->middleware('admin');

Route::post('/widget/list','Widgets\OneController@widgetList')->name('widget_list')->middleware('admin');