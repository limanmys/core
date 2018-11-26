<?php

namespace App\Http\Middleware;

use Closure;

class ScriptParameters
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
        dd($request->path());
        return $next($request);
    }
}
