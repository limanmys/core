<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\AuthLog;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login']]);
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $token = auth('api')->attempt($validator->validated());
        if (! $token) {
            return response()->json(['message' => 'Kullanıcı adı veya şifreniz yanlış.'], 401);
        }

        return $this->createNewToken($token, $request);
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth('api')->logout();

        return response()->json(['message' => 'User successfully signed out']);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->createNewToken(auth('api')->refresh());
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function userProfile()
    {
        return response()->json(auth('api')->user());
    }

    /**
     * Get the token array structure.
     *
     * @param  string  $token
     * @return \Illuminate\Http\JsonResponse
     */
    protected function createNewToken($token, Request $request = null)
    {
        User::find(auth('api')->user()->id)->update([
            'last_login_at' => Carbon::now()->toDateTimeString(),
            'last_login_ip' => $request->header('X-Forwarded-For'),
        ]);

        AuthLog::create([
            'user_id' => auth('api')->user()->id,
            'ip_address' => $request->header('X-Forwarded-For'),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
            'expired_at' => (auth('api')->factory()->getTTL() * 60 + time()) * 1000,
            'user' => auth('api')->user(),
        ]);
    }
}
