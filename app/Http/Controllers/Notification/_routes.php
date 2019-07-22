<?php

// Notifications List

Route::get('/bildirimler','Notification\MainController@all')->name('all_user_notifications');

Route::post('/bildirimler','Notification\MainController@check')->name('user_notifications');

Route::post('/bildirim/sil','Notification\MainController@delete')->name('notification_delete');

Route::post('/bildirim/okunanlar/sil','Notification\MainController@delete_read')->name('notification_delete_read');

// Notification Read Route

Route::post('/bildirim/oku','Notification\MainController@read')->name('notification_read');

Route::post('/bildirimler/oku','Notification\MainController@readAll')->name('notifications_read');

Route::view('/bildirim/{notification_id}','notification.one');