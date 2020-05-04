<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

/**
 * Class LogoutController
 * @package App\Http\Controllers\Auth
 */
class LogoutController extends Controller
{
    /**
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function logout()
    {
        system_log(7, "LOGOUT_SUCCESS");
        hook('logout_attempt', [
            "user" => user()
        ]);
        //Logout User
        Auth::logout();
        session()->flush();
        hook('logout_successful');
        //Redirect User
        return respond(route('login'), 300);
    }
}
