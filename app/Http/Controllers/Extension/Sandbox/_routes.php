<?php

Route::post(
    '/lmn/private/sendMail',
    'Extension\Sandbox\InternalController@sendMail'
)->name('SandboxSendMail');

Route::post(
    '/lmn/private/reverseProxyRequest',
    'Extension\Sandbox\InternalController@addProxyConfig'
)->name('SandboxAddVncProxyConfig');
