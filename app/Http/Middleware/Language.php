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
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Check if web session has a locale set for user.
        if (session('locale')) {
            // If so, set that locale in to the app to use it later.
            app()->setLocale(session('locale'));
        } else {
            if (auth()->check()) {
                $locale = auth()->user()->locale ? auth()->user()->locale : env('APP_LANG', 'tr');
                app()->setLocale($locale);
                \Session::put('locale', $locale);
            } else {
                app()->setLocale(env('APP_LANG', 'tr'));
            }
        }

        // Forward request to next target.
        return $next($request);
    }
}
