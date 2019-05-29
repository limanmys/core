<?php

namespace App\Http\Middleware;

use Closure;

class Parameters
{
    public function handle($request, Closure $next,...$parameters)
    {
        // Check for each parameters if it is existing or simply has more characters than 0
        foreach ($parameters as $parameter) {
            if(!request()->has($parameter) || !strlen(request($parameter))){

                // If found something that is missing, abort the process and warn user.
                return respond("Eksik Parametre > $parameter", 403);
            }
        }

        // Forward request to next target.
        return $next($request);
    }
}
