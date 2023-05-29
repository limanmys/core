<?php

namespace App\Http\Middleware;

use Closure;

/**
 * Check If User Is Admin Middleware
 */
class Admin
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Check is User is admin, if not, simply abort.
        if (
            auth()
                ->user()
                ->isAdmin() == false
        ) {
            return respond('Bu işlemi yapmak için yetkiniz yok', 403);
        }

        // Since user is admin, forward request to next target.
        return $next($request);
    }
}
