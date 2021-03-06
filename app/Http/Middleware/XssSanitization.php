<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class XssSanitization
{
    public function handle(Request $request, Closure $next)
    {
        $input = $request->except([
            'password',
            'old_password',
            'password_confirmation',
            'liman_password_baran'
        ]);
        array_walk_recursive($input, function(&$input) {
            $input = strip_tags($input);
        });
        $request->merge($input);
        return $next($request);
    }
}
