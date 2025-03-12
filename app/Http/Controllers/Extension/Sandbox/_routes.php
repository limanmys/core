<?php

Route::prefix('/lmn/private')
    ->middleware(['api'])
    ->group(function () {
        Route::post(
            '/sendMail',
            'Extension\Sandbox\InternalController@sendMail'
        )->name('SandboxSendMail');

        Route::post(
            '/reverseProxyRequest',
            'Extension\Sandbox\InternalController@addProxyConfig'
        )->name('SandboxAddVncProxyConfig');

        Route::post(
            '/getLimanUsers',
            'Extension\Sandbox\InternalController@getLimanUsers'
        )->name('SandboxGetLimanUsers');
    });
