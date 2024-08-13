<?php

namespace App\Http\Middleware;

use App\Models\AccessToken;
use App\Models\User;
use Carbon\Carbon;
use Closure;
use Illuminate\Support\Facades\Auth;

/**
 * API Token Checker Middleware
 */
class APILogin
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (request()->headers->has('liman-token')) {
            $obj = AccessToken::where([
                'token' => request()->headers->get('liman-token'),
            ])->first();
            if (! $obj) {
                return response()->json([
                    'message' => 'Invalid token.',
                ], 403);
            }

            if ($obj->ip_range != '-1' && ! ip_in_range($request->ip(), $obj->ip_range)) {
                return response()->json([
                    'message' => 'You are restricted to access the site.',
                ], 403);
            }

            $obj->update([
                'last_used_at' => Carbon::now()->toDateTimeString(),
                'last_used_ip' => $request->ip(),
            ]);
            
            $token = auth('api')->login(User::find($obj->user_id));
            if (! $token) {
                return response()->json([
                    'message' => 'Invalid token.',
                ], 403);
            }

            $request->headers->set('Authorization', 'Bearer ' . $token);
        }

        return $next($request);
    }
}
