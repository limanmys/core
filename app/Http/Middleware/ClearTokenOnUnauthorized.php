<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ClearTokenOnUnauthorized
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
        $response = $next($request);

        if ($response->getStatusCode() === 401) {
            // Clear token
            try {
                auth('api')->logout();
            } catch (\Throwable $e) {}

            return $response->withoutCookie('token')
                ->withoutCookie('currentUser');
        }

        return $response;
    }
}
