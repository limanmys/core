<?php

namespace App\Http\Controllers\API\Settings;

use App\Http\Controllers\Controller;
use App\Models\AccessToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AccessTokenController extends Controller
{
    /**
     * List users access tokens
     *
     * @return JsonResponse
     */
    public function index()
    {
        return auth('api')->user()
            ->accessTokens()
            ->get();
    }

    /**
     * Create access tokens
     *
     * @return JsonResponse
     */
    public function create(Request $request)
    {
        $token = Str::uuid();
        AccessToken::create([
            'user_id' => auth('api')->user()->id,
            'name' => $request->name,
            'token' => $token,
            'ip_range' => $request->ip_range,
        ]);

        return response()->json([
            'token' => $token
        ]);
    }

    /**
     * Revoke access tokens
     *
     * @return JsonResponse
     */
    public function delete(Request $request)
    {
        $token = AccessToken::find($request->token_id);
        if (! $token || $token->user_id != user()->id) {
            return response()->json([
                'message' => 'Anahtar bulunamadı.'
            ], 404);
        }
        $token->delete();

        return response()->json([
            'message' => 'Anahtar başarıyla silindi.'
        ]);
    }
}
