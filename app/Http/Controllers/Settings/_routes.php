<?php

// Settings Route

Route::get('/ayarlar', 'Settings\MainController@index')
    ->name('settings')
    ->middleware('admin');

Route::get('/ayarlar/{user}', 'Settings\MainController@one')
    ->name('settings_one')
    ->middleware('admin');

Route::post('/ayarlar/liste', 'Settings\MainController@getList')
    ->name('settings_get_list')
    ->middleware('admin');

Route::post('/ayar/yetki/ekle', 'Settings\MainController@addList')
    ->name('settings_add_to_list')
    ->middleware('admin');

Route::post('/ayar/yetki/veriOku', 'Settings\MainController@getPermisssionData')
    ->name('get_permission_data')
    ->middleware('admin');

Route::post(
    '/ayar/yetki/veriYaz',
    'Settings\MainController@writePermisssionData'
)
    ->name('write_permission_data')
    ->middleware('admin');

Route::post('/ayar/yetki/sil', 'Settings\MainController@removeFromList')
    ->name('settings_remove_from_list')
    ->middleware('admin');

Route::post('/ayar/sunucuGrubu/ekle', 'Settings\MainController@addServerGroup')
    ->name('add_server_group')
    ->middleware('admin');

Route::post(
    '/ayar/sunucuGrubu/duzenle',
    'Settings\MainController@modifyServerGroup'
)
    ->name('modify_server_group')
    ->middleware('admin');

Route::post('/ayar/log/kaydet', 'Settings\MainController@saveLogSystem')
    ->name('save_log_system')
    ->middleware('admin');

Route::get('/market/yonlendir', 'Settings\MainController@redirectMarket')
    ->name('redirect_market')
    ->middleware('admin');
Route::get('/market/baglaAuth', 'Settings\MainController@connectMarket')
    ->name('connect_market')
    ->middleware('admin');

Route::post('/ayar/log/oku', 'Settings\MainController@getLogSystem')
    ->name('get_log_system')
    ->middleware('admin');

Route::post(
    '/ayar/sunucuGrubu/sil',
    'Settings\MainController@deleteServerGroup'
)
    ->name('delete_server_group')
    ->middleware('admin');

Route::post('/ayar/kisitliMod', 'Settings\MainController@restrictedMode')
    ->name('restricted_mode_update')
    ->middleware('admin');

Route::view('/ayar/sunucu', 'settings.server')
    ->middleware('admin')
    ->name('settings_server');

Route::post(
    '/ayar/eklenti/fonksiyonlar',
    'Settings\MainController@getExtensionFunctions'
)
    ->middleware('admin')
    ->name('extension_function_list');

Route::post(
    '/ayar/eklenti/fonksiyonlar/ekle',
    'Settings\MainController@addFunctionPermissions'
)
    ->middleware('admin')
    ->name('extension_function_add');

Route::post(
    '/ayar/eklenti/fonksiyonlar/sil',
    'Settings\MainController@removeFunctionPermissions'
)
    ->middleware('admin')
    ->name('extension_function_remove');

Route::post('/ayar/ldap', 'Settings\MainController@saveLDAPConf')
    ->middleware('admin')
    ->name('save_ldap_conf');

Route::post('/ayarlar/saglik', 'Settings\MainController@health')
    ->middleware('admin')
    ->name('health_check');

Route::post('/kullaniciGetir', 'Settings\MainController@getUserList')
    ->middleware('admin')
    ->name('get_user_list_admin');

Route::view('/sifreDegistir', 'user.password')
    ->middleware('auth')
    ->name('password_change');

Route::post('/sifreDegistir', 'UserController@forcePasswordChange')
    ->middleware('auth')
    ->name('password_change_save');

Route::post('/dnsOku', 'Settings\MainController@getDNSServers')
    ->middleware('admin')
    ->name('get_liman_dns_servers');

Route::post('/dnsYaz', 'Settings\MainController@setDNSServers')
    ->middleware('admin')
    ->name('set_liman_dns_servers');
