<?php

namespace App\Http\Controllers\API;

use App\Classes\Authentication\Handoff\AuthenticationHandoffService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AuthHandoffController extends Controller
{
    public function exchange(Request $request, AuthenticationHandoffService $handoff): JsonResponse
    {
        $clientId = (string) $request->getUser();
        $clientSecret = (string) $request->getPassword();
        if ($clientId === '' || $clientSecret === '') {
            return $this->error('invalid_client', 401);
        }

        $validator = Validator::make($request->all(), [
            'grant_type' => 'required|in:authorization_code',
            'code' => ['required', 'string', 'regex:/\A[A-Za-z0-9_-]{43}\z/'],
            'code_verifier' => ['required', 'string', 'regex:/\A[A-Za-z0-9\-._~]{43,128}\z/'],
            'redirect_uri' => 'required|string|url|max:2048',
        ]);
        if ($validator->fails()) {
            return $this->error('invalid_request', 400);
        }

        $input = $validator->validated();
        $token = $handoff->exchange(
            $clientId,
            $clientSecret,
            $input['code'],
            $input['code_verifier'],
            $input['redirect_uri'],
        );
        if ($token === null) {
            // Do not reveal whether the client, code, redirect, or verifier was
            // wrong. Every failure is safe to retry only by restarting login.
            return $this->error('invalid_grant', 400);
        }

        return response()->json($token)
            ->header('Cache-Control', 'no-store')
            ->header('Pragma', 'no-cache');
    }

    private function error(string $error, int $status): JsonResponse
    {
        return response()->json(['error' => $error], $status)
            ->header('Cache-Control', 'no-store')
            ->header('Pragma', 'no-cache');
    }
}
