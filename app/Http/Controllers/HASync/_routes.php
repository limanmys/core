<?php

Route::prefix('hasync')
    ->middleware(['block_except_limans', 'api'])
    ->group(function () {
        Route::get('/extension_list', "HASync\MainController@extensionList")->name('ha_extension_list');
        Route::get('/download_extension/{extension_name}', "HASync\MainController@downloadExtension")->name('ha_download_ext');
    });
