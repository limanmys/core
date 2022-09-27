<?php

Route::get('/market/bagla', function () {
    return view('redirect', [
        'url' => route('connect_market', ['code' => request('code'), 'auth' => request('auth')]),
    ]);
});
