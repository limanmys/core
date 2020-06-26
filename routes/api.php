<?php

use Illuminate\Http\Request;

Route::get('/market/bagla', function () {
    return view('redirect', [
        'url' => route('connect_market', ["code" => request('code')]),
    ]);
});
