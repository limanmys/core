<?php

Route::get('/modules','Module\MainController@index')->name('modules_index');

Route::post('/modules/hooks','Module\MainController@getHooks')->name('module_hooks');

Route::post('/modules/hooks/update','Module\MainController@modifyHookStatus')->name('module_hooks_update');

Route::post('/modules/update','Module\MainController@modifyModuleStatus')->name('module_update');

Route::post('/modules/getSettings','Module\MainController@getModuleSettings')->name('module_settings_get');