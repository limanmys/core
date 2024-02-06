<?php

namespace App\Http\Middleware;

use App\Models\AccessToken;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class APILogin
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->header('liman-token');
        
        if (! $token) {
            return $this->abortWithMessage('Token eksik!', Response::HTTP_FORBIDDEN);
        }

        $accessToken = AccessToken::where('token', $token)->first();

        if (! $accessToken) {
            return $this->abortWithMessage('Geçersiz token!', Response::HTTP_FORBIDDEN);
        }

        if (! $this->isIPInRange($request->ip(), $accessToken->ip_range)) {
            return $this->abortWithMessage('Bu IP adresinden token kullanılamaz!', Response::HTTP_FORBIDDEN);
        }

        $this->updateAccessToken($accessToken, $request->ip());

        $this->loginUser($accessToken->user_id);

        return $next($request);
    }

    private function abortWithMessage(string $message, int $statusCode): Response
    {
        abort($statusCode, $message);
    }

    private function isIPInRange(string $ip, string $range): bool
    {
        return $range === '-1' || ip_in_range($ip, $range);
    }

    private function updateAccessToken(AccessToken $accessToken, string $ip): void
    {
        $accessToken->update([
            'last_used_at' => now(),
            'last_used_ip' => $ip,
        ]);
    }

    private function loginUser(int $userId): void
    {
        Auth::loginUsingId($userId);
    }
}
