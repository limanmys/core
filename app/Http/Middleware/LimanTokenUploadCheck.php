<?php

namespace App\Http\Middleware;

use App\Models\Token;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class LimanTokenUploadCheck
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
        $token = "";
        if ($request->token) {
            $token = $request->token;
        } else if ($request->headers->get('Extension-Token')) {
            $token = $request->headers->get('Extension-Token');
        }

        if (! $token) {
            return response()->json([
                'status' => 'error',
                'message' => 'Extension-Token header is missing.',
            ], 401);
        }
        
        $obj = Token::where('token', $token)->first();
        if (! $obj) {
            return response()->json([
                'status' => 'error',
                'message' => 'Extension-Token is invalid.',
            ], 401);
        }

        Log::info('Extension-Token is valid. User ip: ' . $request->ip);

        return $next($request);
    }
}
