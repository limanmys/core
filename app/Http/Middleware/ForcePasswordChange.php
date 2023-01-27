<?php

namespace App\Http\Middleware;

use Closure;

/**
 * Force Password Change Middleware
 * Redirects to password change page if it's set as true
 */
class ForcePasswordChange
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $safeRoutes = ['password_change', 'password_change_save', 'logout'];
        if (
            auth()->check() &&
            user()->forceChange == true &&
            ! in_array(
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
