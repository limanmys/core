<?php

namespace App\Classes\Authentication\Handoff;

use Illuminate\Contracts\Cache\Lock;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

/**
 * Issues encrypted, short-lived authorization codes and atomically exchanges
 * them for Liman JWT payloads. Codes are bound to a confidential client,
 * exact redirect URI, and an RFC 7636 S256 challenge.
 */
class AuthenticationHandoffService
{
    private const CODE_CACHE_PREFIX = 'auth_handoff:code:';

    private const LOCK_CACHE_PREFIX = 'auth_handoff:lock:';

    public function __construct(
        private ?TrustedClientRegistry $clients = null,
    ) {
        $this->clients ??= new TrustedClientRegistry;
    }

    /**
     * @param array<string, mixed> $handoff
     * @return array<string, string>|null
     */
    public function authorizeInitiation(array $handoff): ?array
    {
        return $this->clients->authorizeInitiation($handoff);
    }

    /**
     * @param array<string, string> $handoff
     * @param array<string, mixed> $tokenPayload
     */
    public function issue(array $handoff, array $tokenPayload): string
    {
        $code = $this->randomCode();
        $digest = hash('sha256', $code);
        $ttl = (int) config('auth_handoff.code_ttl', 60);
        $expiresAt = time() + $ttl;

        $payload = [
            'version' => 1,
            'client_id' => $handoff['client_id'],
            'redirect_uri' => $handoff['redirect_uri'],
            'code_challenge' => $handoff['code_challenge'],
            'code_challenge_method' => 'S256',
            'token' => $tokenPayload,
            'expires_at' => $expiresAt,
        ];

        Cache::put(
            self::CODE_CACHE_PREFIX.$digest,
            Crypt::encryptString(json_encode($payload, JSON_THROW_ON_ERROR)),
            $ttl,
        );

        Log::info('Authentication handoff issued', [
            'client_id' => $handoff['client_id'],
            'expires_at' => $expiresAt,
        ]);

        return $code;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function exchange(
        string $clientId,
        string $clientSecret,
        string $code,
        string $codeVerifier,
        string $redirectUri,
    ): ?array {
        if (! $this->clients->authenticate($clientId, $clientSecret)
            || ! $this->clients->redirectRegistered($clientId, $redirectUri)
            || ! preg_match('/\A[A-Za-z0-9_-]{43}\z/', $code)
            || ! preg_match('/\A[A-Za-z0-9\-._~]{43,128}\z/', $codeVerifier)) {
            return null;
        }

        $digest = hash('sha256', $code);
        $lock = Cache::lock(self::LOCK_CACHE_PREFIX.$digest, 5);
        if (! $lock->get()) {
            return null;
        }

        try {
            $encrypted = Cache::get(self::CODE_CACHE_PREFIX.$digest);
            if (! is_string($encrypted)) {
                return null;
            }

            try {
                $payload = json_decode(Crypt::decryptString($encrypted), true, 32, JSON_THROW_ON_ERROR);
            } catch (\Throwable $e) {
                Cache::forget(self::CODE_CACHE_PREFIX.$digest);
                Log::warning('Discarded invalid authentication handoff record', [
                    'client_id' => $clientId,
                ]);

                return null;
            }

            $expectedChallenge = $this->codeChallenge($codeVerifier);
            if (! is_array($payload)
                || ($payload['version'] ?? null) !== 1
                || ($payload['client_id'] ?? null) !== $clientId
                || ($payload['redirect_uri'] ?? null) !== $redirectUri
                || ($payload['code_challenge_method'] ?? null) !== 'S256'
                || ! is_string($payload['code_challenge'] ?? null)
                || ! hash_equals($payload['code_challenge'], $expectedChallenge)
                || ! is_int($payload['expires_at'] ?? null)
                || $payload['expires_at'] < time()
                || ! is_array($payload['token'] ?? null)) {
                return null;
            }

            // Forget while holding the distributed lock: a successful code can
            // be redeemed exactly once, even across multiple Liman replicas.
            Cache::forget(self::CODE_CACHE_PREFIX.$digest);

            Log::info('Authentication handoff redeemed', [
                'client_id' => $clientId,
            ]);

            return $payload['token'];
        } finally {
            $this->release($lock);
        }
    }

    /**
     * @param array<string, string> $handoff
     */
    public function successRedirect(array $handoff, string $code): string
    {
        return $handoff['redirect_uri'].'?'.http_build_query([
            'code' => $code,
            'state' => $handoff['state'],
        ], '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * @param array<string, string> $handoff
     */
    public function errorRedirect(array $handoff, string $error): string
    {
        if (! preg_match('/\A[a-z][a-z0-9_]{0,63}\z/', $error)) {
            $error = 'server_error';
        }

        return $handoff['redirect_uri'].'?'.http_build_query([
            'error' => $error,
            'state' => $handoff['state'],
        ], '', '&', PHP_QUERY_RFC3986);
    }

    private function randomCode(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }

    private function codeChallenge(string $verifier): string
    {
        return rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
    }

    private function release(Lock $lock): void
    {
        try {
            $lock->release();
        } catch (\Throwable $e) {
            Log::warning('Authentication handoff lock release failed');
        }
    }
}
