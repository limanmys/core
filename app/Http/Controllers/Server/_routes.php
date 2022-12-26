<?php

// Servers Route

Route::get('/sunucular', 'Server\MainController@all')->name('servers');

//Server IP Route
Route::get('/sunucuDetayi/{server_id}', 'Server\MainController@oneData')->name('server_get_ip');

// Add Server Route

Route::post('/sunucu/ekle', 'Server\AddController@main')
    ->name('server_add')
    ->middleware('parameters:name,ip_address,control_port');

// Server Update Route

Route::post('/sunucu/guncelle', 'Server\OneController@update')
    ->name('server_update')
    ->middleware('parameters:server_id,name,control_port');

Route::post('/sunucu/erisimKontrolu', 'Server\MainController@checkAccess')
    ->name('server_check_access')
    ->middleware('parameters:hostname,port');

Route::post('/sunucu/isimKontrol', 'Server\MainController@verifyName')
    ->name('server_verify_name')
    ->middleware('parameters:server_name');

Route::post('/sunucu/anahtarKontrol', 'Server\MainController@verifyKey')->name(
    'server_verify_key'
);

// Remove Server Route

Route::post('/sunucu/sil', 'Server\OneController@remove')
    ->name('server_remove')
    ->middleware('parameters:server_id');

// Remove Server Permission

Route::post('/sunucu/yetkial', 'Server\OneController@revoke')
    ->name('server_revoke_permission')
    ->middleware('parameters:user_id,server_id');

Route::group(['middleware' => ['server']], function () {
    // Single Server Details Route

    Route::get('/sunucular/{server_id}', 'Server\OneController@one')->name(
        'server_one'
    );

    // Server' Service Status Route
    Route::post('/sunucu/kontrol', 'Server\OneController@serviceCheck')->name(
        'server_check'
    );

    // Server Hostname Update

    Route::post('/sunucu/hostname', 'Server\OneController@hostname')
        ->name('server_hostname')
        ->middleware('parameters:hostname');

    // Server Service Run,Stop,Enable,Disable Route

    Route::post('/sunucu/servis', 'Server\OneController@service')
        ->name('server_service')
        ->middleware('parameters:extension_id,action');

    // Server Extension Installation Route

    Route::post(
        '/sunucu/eklenti',
        'Server\OneController@enableExtension'
    )->name('server_extension');

    // Server File Upload Route

    Route::post('/sunucu/yukle', 'Server\OneController@upload')
        ->name('server_upload')
        ->middleware('parameters:file,path');

    // Server Download File Route

    Route::get('/sunucu/indir', 'Server\OneController@download')
        ->name('server_download')
        ->middleware('parameters:path');

    // Server Permission Grant Route

    //Route::post('/sunucu/yetkilendir', 'Server\OneController@grant')->name('server_grant_permission')->middleware('parameters:server_id,email');

    Route::post('/sunucu/favori', 'Server\OneController@favorite')
        ->name('server_favorite')
        ->middleware('parameters:server_id,action');

    Route::post('/sunucu/durum', 'Server\OneController@stats')->name(
        'server_stats'
    );

    Route::post(
        '/sunucu/bellek_durum',
        'Server\OneController@topMemoryProcesses'
    )->name('top_memory_processes');

    Route::post(
        '/sunucu/islemci_durum',
        'Server\OneController@topCpuProcesses'
    )->name('top_cpu_processes');

    Route::post(
        '/sunucu/disk_durum',
        'Server\OneController@topDiskUsage'
    )->name('top_disk_usage');

    Route::post('/sunucu/servis/', 'Server\OneController@serviceList')->name(
        'server_service_list'
    );

    Route::post(
        '/sunucu/yetkili_kullanicilar/',
        'Server\OneController@getSudoers'
    )->name('server_sudoers_list');

    Route::post(
        '/sunucu/yetkili_kullanicilar/ekle',
        'Server\OneController@addSudoers'
    )->name('server_add_sudoers');

    Route::post(
        '/sunucu/yetkili_kullanicilar/sil',
        'Server\OneController@deleteSudoers'
    )->name('server_delete_sudoers');

    Route::post(
        '/sunucu/yerel_kullanicilar/',
        'Server\OneController@getLocalUsers'
    )->name('server_local_user_list');

    Route::post(
        '/sunucu/yerel_kullanicilar/ekle',
        'Server\OneController@addLocalUser'
    )->name('server_add_local_user');

    Route::post(
        '/sunucu/yerel_gruplar/',
        'Server\OneController@getLocalGroups'
    )->name('server_local_group_list');

    Route::post(
        '/sunucu/yerel_gruplar/ekle',
        'Server\OneController@addLocalGroup'
    )->name('server_add_local_group');

    Route::post(
        '/sunucu/yerel_gruplar/kullanicilar',
        'Server\OneController@getLocalGroupDetails'
    )->name('server_local_group_users_list');

    Route::post(
        '/sunucu/yerel_gruplar/kullanicilar/ekle',
        'Server\OneController@addLocalGroupUser'
    )->name('server_add_local_group_user');

    Route::post(
        '/sunucu/guncellemeler/',
        'Server\OneController@updateList'
    )->name('server_update_list');

    Route::post(
        '/sunucu/guncellemeler/paket_yukle',
        'Server\OneController@installPackage'
    )->name('server_install_package');

    Route::post(
        '/sunucu/guncellemeler/paket_kontrol',
        'Server\OneController@checkPackage'
    )->name('server_check_package');

    Route::post(
        '/sunucu/guncellemeler/deb_yukle',
        'Server\OneController@uploadDebFile'
    )->name('server_upload_deb');

    Route::post(
        '/sunucu/gunluk_kayitlari',
        'Server\OneController@getLogs'
    )->name('server_get_logs');

    Route::post(
        '/sunucu/accessLogs',
        'Server\OneController@accessLogs'
    )->name('server_access_logs');

    Route::post(
        '/sunucu/gunluk_kayitlari_detay',
        'Server\OneController@getLogDetails'
    )->name('server_get_log_details');

    Route::post('/sunucu/paketler', 'Server\OneController@packageList')->name(
        'server_package_list'
    );

    Route::post(
        '/sunucu/eklentiSil',
        'Server\OneController@removeExtension'
    )->name('server_extension_remove');

    Route::post(
        '/sunucu/servis/baslat',
        'Server\OneController@startService'
    )->name('server_start_service');

    Route::post(
        '/sunucu/servis/durdur',
        'Server\OneController@stopService'
    )->name('server_stop_service');

    Route::post(
        '/sunucu/servis/yenidenBaslat',
        'Server\OneController@restartService'
    )->name('server_restart_service');

    Route::post(
        '/sunucu/servis/durum',
        'Server\OneController@statusService'
    )->name('server_service_status');

    Route::post(
        '/sunucu/acikPortlar',
        'Server\OneController@getOpenPorts'
    )->name('server_get_open_ports');
});
