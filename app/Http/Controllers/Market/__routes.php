<?php

Route::middleware(["admin"])->group(function () {
    Route::post(
        '/market/kontrol',
        'Market\\MarketController@verifyMarketConnection'
    )
        ->name('verify_market');
    
    Route::post(
        '/market/guncellemeKontrol',
        'Market\\MarketController@checkMarketUpdates'
    )
        ->name('check_updates_market');
    
    // Public
    
    Route::get(
        '/market',
        'Market\\PublicController@getApplications'
    )
        ->name("market");

    Route::get(
        '/market/kategori/{category_id}',
        'Market\\PublicController@getCategoryItems'
    )
        ->name("market_kategori");

    Route::get(
        '/market/arama',
        function(Illuminate\Http\Request $request) {
            return redirect()->route('market_search_real', ["search_query" => $request["search_query"]]);    
        }
    )
        ->name('market_search');
    
    Route::get(
        '/market/arama/{search_query}',
        'Market\\PublicController@search'
    )
        ->name('market_search_real');

    Route::post(
        '/market/kur/{package_name}',
        'Market\\PublicController@installPackage'
    )
        ->name('market_install_package');

    Route::get(
        '/market/testfield',
        'Market\\PublicController@test'
    )
        ->name("test");
});