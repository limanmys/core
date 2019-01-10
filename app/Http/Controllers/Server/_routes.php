<?php


// Servers Route

Route::get('/sunucular', 'Server\MainController@all')->name('servers');

// Add Server Route

Route::post('/sunucu/ekle', 'Server\AddController@main')->name('server_add')->middleware('parameters:ip_address,control_port,type,city');

// Server Status Route (Telnet)

Route::post('/server/kontrol', 'Server\MainController@isAlive')->middleware('parameters:ip,port');

// Single Server Details Route

Route::get('/sunucular/{server_id}', 'Server\OneController@one')->name('server_one');

// Server Update Route

Route::post('/sunucu/guncelle', 'Server\OneController@update')->name('server_update')->middleware('parameters:server_id,name,control_port,city');

// Server' Service Status Route

Route::post('/sunucu/kontrol', 'Server\OneController@serviceCheck')->name('server_check')->middleware('parameters:service,server_id');

// Server Network Update

Route::post('/sunucu/network', 'Server\OneController@network')->name('server_network')->middleware('parameters:ip,cidr,gateway,interface,password');

// Server Hostname Update

Route::post('/sunucu/hostname', 'Server\OneController@hostname')->name('server_hostname')->middleware('parameters:hostname');

// Server Service Run,Stop,Enable,Disable Route

Route::post('/sunucu/servis', 'Server\OneController@service')->name('server_service')->middleware('parameters:extension_id,action');

// Server Extension Installation Route

Route::post('/sunucu/eklenti', 'Server\OneController@enableExtension')->name('server_extension');

// Server File Upload Route

Route::post('/sunucu/yukle','Server\OneController@upload')->name('server_upload')->middleware('parameters:file,path');

// Server Terminal Route

Route::get('/sunucu/terminal', 'Server\OneController@terminal')->name('server_terminal');

// Server Download File Route

Route::get('/sunucu/indir', 'Server\OneController@download')->name('server_download')->middleware('parameters:path');

// Server Permission Grant Route

Route::post('/sunucu/yetkilendir', 'Server\OneController@grant')->name('server_grant_permission')->middleware('parameters:server_id,email');

// Remove Server Route

Route::post('/sunucu/sil', 'Server\OneController@remove')->name('server_remove')->middleware('parameters:server_id');