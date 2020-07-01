<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\AccessToken;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class APILogin
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
        if (request()->headers->has("liman-token")) {
            $obj = AccessToken::where([
                "token" => request()->headers->get("liman-token"),
            ])->first();
            if (!$obj) {
                abort(403, "Token Geçersiz!");
            }
            $obj->update([
                "last_used_at" => Carbon::now()->toDateTimeString(),
                "last_used_ip" => $request->ip(),
            ]);
            Auth::loginUsingId($obj->user_id);
        }
        return $next($request);
    }
}
