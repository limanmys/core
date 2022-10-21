<?php

Route::post(
    '/mail/oku',
    'CronMail\MainController@getCronMail'
)->name('cron_mail_get')->middleware('admin');

Route::post(
    '/mail/ekle',
    'CronMail\MainController@addCronMail'
)->name('cron_mail_add')->middleware('admin');

Route::post(
    '/mail/sil',
    'CronMail\MainController@deleteCronMail'
)->name('cron_mail_delete')->middleware('admin');

Route::post(
    '/mail/taglariOKu',
    'CronMail\MainController@getMailTags'
)->name('cron_mail_get_tags')->middleware('admin');

Route::post(
    '/mail/gonder',
    'CronMail\MainController@sendNow'
)->name('cron_mail_now')->middleware('admin');

Route::get(
    '/mail/ekle',
    'CronMail\MainController@getView'
)->name('cron_mail_add_page')->middleware('admin');

Route::get(
    '/mail/edit/{id}',
    'CronMail\MainController@editView'
)->name('cron_mail_edit_page')->middleware('admin');

Route::post(
    '/mail/edit/{id}',
    'CronMail\MainController@edit'
)->name('cron_mail_edit')->middleware('admin');
