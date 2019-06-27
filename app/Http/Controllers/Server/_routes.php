<?php


// Servers Route

Route::view('/sunucular', 'server.index')->name('servers');

// Add Server Route

Route::post('/sunucu/ekle', 'Server\AddController@main')->name('server_add')->middleware('parameters:ip_address,control_port,type,city');

// Server Update Route

Route::post('/sunucu/guncelle', 'Server\OneController@update')->name('server_update')->middleware('parameters:server_id,name,control_port,city');


// Remove Server Route

Route::post('/sunucu/sil', 'Server\OneController@remove')->name('server_remove')->middleware('parameters:server_id');

// Remove Server Permission

Route::post('/sunucu/yetkial', 'Server\OneController@revoke')->name('server_revoke_permission')->middleware('parameters:user_id,server_id');

Route::group(['middleware' => ['server']], function () {


    // Single Server Details Route

    Route::get('/sunucular/{server_id}', 'Server\OneController@one')->name('server_one');

    // Server' Service Status Route
    Route::post('/sunucu/kontrol', 'Server\OneController@serviceCheck')->name('server_check')->middleware('parameters:service,server_id');

    // Server Hostname Update

    Route::post('/sunucu/hostname', 'Server\OneController@hostname')->name('server_hostname')->middleware('parameters:hostname');

    // Server Service Run,Stop,Enable,Disable Route

    Route::post('/sunucu/servis', 'Server\OneController@service')->name('server_service')->middleware('parameters:extension_id,action');

    // Server Extension Installation Route

    Route::post('/sunucu/eklenti', 'Server\OneController@enableExtension')->name('server_extension');

    // Server File Upload Route

    Route::post('/sunucu/yukle', 'Server\OneController@upload')->name('server_upload')->middleware('parameters:file,path');

    // Server Terminal Route

    Route::get('/sunucu/terminal', 'Server\OneController@terminal')->name('server_terminal');

    // Server Download File Route

    Route::get('/sunucu/indir', 'Server\OneController@download')->name('server_download')->middleware('parameters:path');

    // Server Permission Grant Route

    Route::post('/sunucu/yetkilendir', 'Server\OneController@grant')->name('server_grant_permission')->middleware('parameters:server_id,email');

    Route::post('/sunucu/favori','Server\OneController@favorite')->name('server_favorite')->middleware('parameters:server_id,action');

    Route::post('/sunucu/durum','Server\OneController@stats')->name('server_stats');

    Route::post('/sunucu/servis/','Server\OneController@serviceList')->name('server_service_list');

    Route::post('/sunucu/paketler','Server\OneController@packageList')->name('server_package_list');

    Route::post('/sunucu/yukselt','Server\OneController@upgradeServer')->name('server_upgrade');
});