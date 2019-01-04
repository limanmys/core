<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class Parameters
{
    public function handle($request, Closure $next,...$parameters)
    {
        foreach ($parameters as $parameter) {
            if(!$request->has($parameter)){
                return $this->response("Missing parameter > $parameter");
            }
        }
        $request->request->add(['user_id' => (Auth::check()) ? Auth::id() : 0]);
        return $next($request);
    }

    private function response($message)
    {
        if (request()->wantsJson()) {
            return response([
                "message" => $message
            ], 403);
        }else{
            return response()->view('general.error', [
                "message" => $message
            ]);    
        }
    }
}
