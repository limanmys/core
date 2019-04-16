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
        //Logout User
        Auth::logout();
        session()->flush();

        //Redirect User
        return respond(route('login'),300);
    }
}
