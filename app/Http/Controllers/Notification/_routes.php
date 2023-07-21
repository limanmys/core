<?php

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
