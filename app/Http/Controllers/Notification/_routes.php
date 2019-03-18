<?php

// Notifications List

Route::post('/bildirimler','Notification\MainController@check')->name('user_notifications');

// Notification Read Route

Route::post('/bildirim/oku','Notification\MainController@read')->name('notification_read');

Route::post('/bildirimler/oku','Notification\MainController@readAll')->name('notifications_read');

Route::view('/bildirim/{notification_id}','notification.one');