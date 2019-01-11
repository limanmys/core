<?php

namespace App\Http\Middleware;

use Closure;

class Parameters
{
    public function handle($request, Closure $next,...$parameters)
    {
        foreach ($parameters as $parameter) {
            if(!request()->has($parameter) || !strlen(request($parameter))){
                return respond("Missing parameter > $parameter", 403);
            }
        }
        return $next($request);
    }
}
