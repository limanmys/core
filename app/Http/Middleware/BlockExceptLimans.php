<?php

namespace App\Http\Middleware;

use App\Models\Liman;
use Closure;
use Illuminate\Http\Request;

class BlockExceptLimans
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
        $ips = Liman::all()->pluck(["last_ip"])->toArray();

        if (!in_array($request->ip(), $ips)) {
            abort(403, "You are restricted to access the site.");
        }

        return $next($request);
    }
}
