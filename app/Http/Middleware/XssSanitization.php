<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * XSS Sanitization Middleware
 */
class XssSanitization
{
    /**
     * Handles an incoming request
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $input = $request->except([
            'password',
            'old_password',
            'password_confirmation',
            'liman_password_divergent',
        ]);
        array_walk_recursive($input, function (&$input, $key) {
            if (! str_contains(strtolower($key), 'password')) {
                $input = strip_tags($input);
            }
        });
        $request->merge($input);

        return $next($request);
    }
}
