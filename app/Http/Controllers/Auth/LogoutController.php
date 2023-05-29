<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Oauth2Token;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

/**
 * Class LogoutController
 *
 * @extends Controller
 */
class LogoutController extends Controller
{
    /**
     * Handles logout
     *
     * @return RedirectResponse
     */
    public function logout(): \Illuminate\Http\RedirectResponse
    {
        system_log(7, 'LOGOUT_SUCCESS');
        hook('logout_attempt', [
            'user' => user(),
        ]);

        $user_type = auth()->user()->auth_type;
        $user_id = auth()->user()->id;

        Auth::guard()->logout();

        request()
            ->session()
            ->invalidate();

        request()
            ->session()
            ->regenerateToken();
        
        hook('logout_successful');

        if ($user_type == 'keycloak') {
            Oauth2Token::where('user_id', $user_id)->delete();

            return redirect(Socialite::driver('keycloak')->getLogoutUrl());
        }
        return redirect(route('login'));
    }
}
