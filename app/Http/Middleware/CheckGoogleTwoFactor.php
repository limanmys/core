<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckGoogleTwoFactor
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (env('OTP_ENABLED')) {
            if (! auth()->user()->google2fa_secret) {
                return redirect()->route('registerGoogleAuth');
            }
        }

        return $next($request);
    }
}
