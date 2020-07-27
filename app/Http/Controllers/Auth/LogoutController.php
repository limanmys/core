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
            "user" => user(),
        ]);
        Auth::guard()->logout();
        request()
            ->session()
            ->invalidate();
        request()
            ->session()
            ->regenerateToken();
        hook('logout_successful');
        return redirect(route('login'));

    }
}
