<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\App;

class Language
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
        // Check if web session has a locale set for user.
        if ($request->session()->has('locale')) {

            // If so, set that locale in to the app to use it later.
            $locale = $request->session()->get('locale');
            App::setLocale($locale);
        }

        // Check if session has dark mode set.
        if($request->session()->has('dark')){
            $request->request->add(['dark_mode' => "true"]);
        }
        
        // Forward request to next target.
        return $next($request);
    }
}
