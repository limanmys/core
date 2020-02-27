<?php

Route::post('/rol/ekle','Roles\RoleController@add')->name('role_add')->middleware('admin');

Route::post('/rol/sil','Roles\RoleController@remove')->name('role_remove')->middleware('admin');

Route::post('/rol/liste','Roles\RoleController@list')->name('role_list')->middleware('admin');

Route::get('/rol/{role}','Roles\RoleController@one')->name('role_one')->middleware('admin');

Route::post('/rol/kullanici_ekle','Roles\RoleController@addRoleUsers')->name('add_role_users')->middleware('admin');

Route::post('/rol/rol_ekle','Roles\RoleController@addRolesToUser')->name('add_roles_to_user')->middleware('admin');

Route::post('/rol/rol_sil','Roles\RoleController@removeRolesToUser')->name('remove_roles_to_user')->middleware('admin');

Route::post('/rol/kullanici_sil','Roles\RoleController@removeRoleUsers')->name('remove_role_users')->middleware('admin');

Route::post('/rol/yetki_listesi','Roles\RoleController@getList')->name('role_permission_list')->middleware('admin');

Route::post('/rol/yetki_listesi/ekle','Roles\RoleController@addList')->name('add_role_permission_list')->middleware('admin');

Route::post('/rol/yetki_listesi/sil','Roles\RoleController@removeFromList')->name('remove_role_permission_list')->middleware('admin');

Route::post('/rol/yetki_listesi/fonksiyon_ekle','Roles\RoleController@addFunctionPermissions')->name('add_role_function')->middleware('admin');

Route::post('/rol/yetki_listesi/fonksiyon_sil','Roles\RoleController@removeFunctionPermissions')->name('remove_role_function')->middleware('admin');

Route::post('/rol/domain_gruplari','Roles\RoleMappingController@fetchDomainGroups')->name('fetch_domain_groups')->middleware('admin');

Route::post('/rol/domain_kullanicilari','Roles\RoleMappingController@fetchDomainUsers')->name('fetch_domain_users')->middleware('admin');

Route::post('/rol/kisitlama_ekle','Roles\RoleMappingController@addLdapRestriction')->name('add_ldap_restriction')->middleware('admin');

Route::post('/rol/kisitlama_sil','Roles\RoleMappingController@deleteLdapRestriction')->name('delete_ldap_restriction')->middleware('admin');

Route::post('/rol/eslestirme_ekle','Roles\RoleMappingController@add')->name('add_role_mapping')->middleware('admin');

Route::post('/rol/eslestirme_sil','Roles\RoleMappingController@delete')->name('delete_role_mapping')->middleware('admin');
