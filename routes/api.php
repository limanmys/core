<?php

use Illuminate\Http\Request;

Route::get('/market/bagla',function(){
    return redirect(route('connect_market',["code" => request('code')]));
});