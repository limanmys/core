<?php

Route::post(
    '/lmn/private/dispatchJob',
    'Extension\Sandbox\InternalController@dispatchJob'
)->name('SandboxDispatchJob');

Route::post(
    '/lmn/private/getJobList',
    'Extension\Sandbox\InternalController@getJobList'
)->name('SandboxGetJobList');

Route::post(
    '/lmn/private/extensionApi',
    'Extension\Sandbox\InternalController@internalExtensions'
);

Route::post(
    '/lmn/private/sendNotification',
    'Extension\Sandbox\InternalController@sendNotification'
)->name('SandboxSendNotification');

Route::post(
    '/lmn/private/runCommandApi',
    'Extension\Sandbox\InternalController@runCommand'
)->name('SandboxRunCommand');

Route::post(
    '/lmn/private/runScriptApi',
    'Extension\Sandbox\InternalController@runScript'
)->name('SandboxRunScript');

Route::post(
    '/lmn/private/putFileApi',
    'Extension\Sandbox\InternalController@putFile'
)->name('SandboxPutFile');

Route::post(
    '/lmn/private/getFileApi',
    'Extension\Sandbox\InternalController@getFile'
)->name('SandboxGetFile');

Route::post(
    '/lmn/private/openTunnel',
    'Extension\Sandbox\InternalController@openTunnel'
)->name('SandboxOpenSSHTunnel');

Route::post(
    '/lmn/private/stopTunnel',
    'Extension\Sandbox\InternalController@stopTunnel'
)->name('SandboxStopSSHTunnel');

Route::post(
    '/lmn/private/reverseProxyRequest',
    'Extension\Sandbox\InternalController@addProxyConfig'
)->name('SandboxAddVncProxyConfig');
