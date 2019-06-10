<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;

/**
 * Class LoginController
 * @package App\Http\Controllers\Auth
 */
class LoginController extends Controller
{
    use AuthenticatesUsers;

    /**
     * @var string
     */
    protected $redirectTo = '/';

    /**
     * LoginController constructor.
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function authenticated(Request $request, $user)
    {
        $user->last_login_at = Carbon::now()->toDateTimeString();
        $user->last_login_ip = $request->ip();
        $user->save();

        system_log(7,"LOGIN_SUCCESS");
    }

    public function attemptLogin(Request $request)
    {
        $flag =  $this->guard()->attempt(
            $this->credentials($request), $request->filled('remember')
        );
        if(!$flag){
            system_log(5,"LOGIN_FAILED");
        }
        return $flag;
    }
}
