<?php

Route::prefix("hasync")
    ->middleware(["block_except_limans", "api"])
    ->group(function () {
        Route::get("/extension_list", "HASync\MainController@extensionList")->name("ha_extension_list");
        Route::get("/module_list", "HASync\MainController@moduleList")->name("ha_module_list");
        Route::get("/download_extension/{extension_name}", "HASync\MainController@downloadExtension")->name("ha_download_ext");
        Route::get("/download_module/{module_name}", "HASync\MainController@downloadModule")->name("ha_download_module");
    });