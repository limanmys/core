<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * Parameter Checker Middleware
 */
class Parameters
{
    /**
     * Check if all parameters has more characters than zero
     *
     * @param $request
     * @param Closure $next
     * @param ...$parameters
     * @return JsonResponse|Response|mixed
     */
    public function handle($request, Closure $next, ...$parameters)
    {
        // Check for each parameters if it is existing or simply has more characters than 0
        foreach ($parameters as $parameter) {
            if (! request()->has($parameter) || ! strlen((string) request($parameter))) {
                // If found something that is missing, abort the process and warn user.
                return respond('Eksik Parametre > ' . $parameter, 403);
            }
        }
        // Forward request to next target.
        return $next($request);
    }
}
