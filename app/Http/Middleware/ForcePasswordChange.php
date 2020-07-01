<?php

namespace App\Http\Middleware;

use Closure;

class ForcePasswordChange
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $safeRoutes = ['password_change', 'password_change_save', 'logout'];
        if (
            auth()->check() &&
            user()->forceChange == true &&
            !in_array(
                request()
                    ->route()
                    ->getName(),
                $safeRoutes
            )
        ) {
            return redirect(route('password_change'));
        }
        return $next($request);
    }
}
