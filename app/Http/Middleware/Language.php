<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class Language
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Check if web session has a locale set for user.
        if (session('locale')) {

            // If so, set that locale in to the app to use it later.
            app()->setLocale(session('locale'));
        }

        // Forward request to next target.
        return $next($request);
    }
}
