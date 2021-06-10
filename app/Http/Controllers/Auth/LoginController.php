<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;

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

    protected $maxAttempts = 5;

    protected $decayMinutes = 10;

    /**
     * LoginController constructor.
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function captcha(){
        return captcha_img();
    }

    public function authenticated(Request $request, $user)
    {
        $user->update([
            "last_login_at" => Carbon::now()->toDateTimeString(),
            "last_login_ip" => $request->ip(),
        ]);

        system_log(7, "LOGIN_SUCCESS");

        hook("login_successful", [
            "user" => $user,
        ]);
    }

    public function attemptLogin(Request $request)
    {
        $credientials = (object) $this->credentials($request);

        $flag = $this->guard()->attempt(
            $this->credentials($request),
            $request->filled('remember')
        );

        Event::listen('login_attempt_success', function ($data) use (&$flag) {
            $this->guard()->login($data, request()->filled('remember'));
            $flag = true;
        });

        if (!$flag) {
            event('login_attempt', $credientials);
        }

        return $flag;
    }

    protected function validateLogin(Request $request)
    {
        $request->request->add([
            $this->username() => $request->liman_email_mert,
            "password" => $request->liman_password_baran,
        ]);
        if (env('EXTENSION_DEVELOPER_MODE'))
        {
            $request->validate([
                $this->username() => 'required|string',
                'password' => 'required|string'
            ]);
        } else {
            $request->validate([
                $this->username() => 'required|string',
                'password' => 'required|string',
                'captcha' => 'required|captcha'
            ]);
        }
    }

    protected function sendFailedLoginResponse(Request $request)
    {
        $credientials = (object) $this->credentials($request);
        hook('login_failed', [
            "email" => $credientials->email,
            "password" => $credientials->password,
        ]);

        throw ValidationException::withMessages([
            $this->username() => [trans('auth.failed')],
        ]);
    }
}
