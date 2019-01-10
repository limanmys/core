<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;

class LogoutController extends Controller
{
    public function logout(){
        \Auth::logout();
        return respond(route('login'),300);
    }
}
