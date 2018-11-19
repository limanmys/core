<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class Parameters
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next,...$parameters)
    {
        foreach ($parameters as $parameter) {
            if(!$request->has($parameter)){
                return response()->json([
                    "result" => [
                        "message" => "Missing parameter > $parameter",
                        "code" => 403
                    ],
                    "data" => []
                ],403);
            }
        }
        $request->request->add(['user_id' => (Auth::check()) ? Auth::id() : 0]);
        return $next($request);
    }
}
