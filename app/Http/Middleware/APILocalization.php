<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class APILocalization
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
        $lang = ($request->hasHeader('X-Language')) 
            ? $request->header('X-Language') 
            : env('APP_LANG', 'tr');
            
        app()->setLocale($lang);

        return $next($request);
    }
}
