<?php

namespace App\Http\Middleware;

use App\Models\Liman;
use Closure;
use Illuminate\Http\Request;

/**
 * Block Except Limans
 * This middleware is for protecting endpoints except Limans
 *
 * It's used on high availability sync service.
 */
class BlockExceptLimans
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse) $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $ips = Liman::all()->pluck(["last_ip"])->toArray();

        if (!in_array($request->ip(), $ips)) {
            return response()->json([
                "message" => "You are restricted to access the site."
            ], 403);
        }

        return $next($request);
    }
}
