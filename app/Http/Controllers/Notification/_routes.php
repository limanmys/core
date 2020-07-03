<?php

// Notifications List

Route::get('/bildirimler', 'Notification\MainController@all')->name(
    'all_user_notifications'
);

Route::get('/bildirimlerSistem', 'Notification\MainController@allSystem')
    ->name('all_system_notifications')
    ->middleware('admin');

Route::post('/bildirimler', 'Notification\MainController@check')->name(
    'user_notifications'
);

Route::post('/bildirim/sil', 'Notification\MainController@delete')->name(
    'notification_delete'
);

Route::post(
    '/bildirim/okunanlar/sil',
    'Notification\MainController@delete_read'
)->name('notification_delete_read');

// Notification Read Route

Route::post('/bildirim/oku', 'Notification\MainController@read')->name(
    'notification_read'
);

Route::post('/bildirimler/oku', 'Notification\MainController@readAll')->name(
    'notifications_read'
);

Route::view('/bildirim/{notification_id}', 'notification.one');

Route::view('/sistemBildirimi/{notification_id}', 'notification.system')
    ->name('system_notification')
    ->middleware('admin');

Route::post('/bildirim/adminOku', 'Notification\MainController@adminRead')
    ->name('notification_admin_read')
    ->middleware('admin');

Route::post(
    '/ayar/bildirimKanali/ekle',
    'Notification\ExternalNotificationController@create'
)
    ->name('add_notification_channel')
    ->middleware('admin');

Route::post(
    '/ayar/bildirimKanali/duzenle',
    'Notification\ExternalNotificationController@edit'
)
    ->name('edit_notification_channel')
    ->middleware('admin');

Route::post(
    '/ayar/bildirimKanali/sil',
    'Notification\ExternalNotificationController@revoke'
)
    ->name('revoke_notification_channel')
    ->middleware('admin');

Route::post(
    '/ayar/bildirimKanali/yenile',
    'Notification\ExternalNotificationController@renew'
)
    ->name('renew_notification_channel')
    ->middleware('admin');
