<?php

Route::post(
    '/market/kontrol',
    'Market\\MarketController@verifyMarketConnection'
)
    ->name('verify_market')
    ->middleware('admin');

Route::post(
    '/market/guncellemeKontrol',
    'Market\\MarketController@checkMarketUpdates'
)
    ->name('check_updates_market')
    ->middleware('admin');