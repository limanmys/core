<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

/**
 * Class LogoutController
 */
class LogoutController extends Controller
{
    public function logout(): \Illuminate\Http\RedirectResponse
    {
        system_log(7, 'LOGOUT_SUCCESS');
        hook('logout_attempt', [
            'user' => user(),
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
