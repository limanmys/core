<?php

namespace App\Classes\Authentication\Handoff;

/**
 * Resolves confidential applications that may receive a Liman authentication
 * handoff. Redirect URIs are always exact-match registered values.
 */
class TrustedClientRegistry
{
    /** @var array<string, mixed> */
    private array $clients;

    private bool $allowInsecureLoopback;

    /**
     * @param array<string, mixed>|null $clients
     */
    public function __construct(?array $clients = null, ?bool $allowInsecureLoopback = null)
    {
        $this->clients = $clients ?? (array) config('auth_handoff.clients', []);
        $this->allowInsecureLoopback = $allowInsecureLoopback
            ?? (bool) config('auth_handoff.allow_insecure_loopback', false);
    }

    /**
     * Return the canonical handoff fields safe to persist with OIDC state.
     *
     * @param array<string, mixed> $handoff
     * @return array<string, string>|null
     */
    public function authorizeInitiation(array $handoff): ?array
    {
        $clientId = $handoff['client_id'] ?? null;
        $redirectUri = $handoff['redirect_uri'] ?? null;
        $state = $handoff['state'] ?? null;
        $codeChallenge = $handoff['code_challenge'] ?? null;
        $challengeMethod = $handoff['code_challenge_method'] ?? null;

        if (! is_string($clientId)
            || ! preg_match('/\A[A-Za-z0-9_-]{1,64}\z/', $clientId)
            || ! is_string($redirectUri)
            || ! is_string($state)
            || ! preg_match('/\A[A-Za-z0-9_-]{32,128}\z/', $state)
            || ! is_string($codeChallenge)
            || ! preg_match('/\A[A-Za-z0-9_-]{43}\z/', $codeChallenge)
            || $challengeMethod !== 'S256') {
            return null;
        }

        $client = $this->client($clientId);
        if ($client === null || ! $this->redirectAllowed($client, $redirectUri)) {
            return null;
        }

        return [
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'state' => $state,
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
        ];
    }

    public function authenticate(string $clientId, string $secret): bool
    {
        $client = $this->client($clientId);
        $expected = $client['secret'] ?? null;

        if (! is_string($expected) || strlen($expected) < 32 || strlen($secret) < 32) {
            return false;
        }

        return hash_equals($expected, $secret);
    }

    public function redirectRegistered(string $clientId, string $redirectUri): bool
    {
        $client = $this->client($clientId);

        return $client !== null && $this->redirectAllowed($client, $redirectUri);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function client(string $clientId): ?array
    {
        if (! preg_match('/\A[A-Za-z0-9_-]{1,64}\z/', $clientId)) {
            return null;
        }

        $client = $this->clients[$clientId] ?? null;
        if (! is_array($client) || ($client['enabled'] ?? true) !== true) {
            return null;
        }

        return $client;
    }

    /**
     * @param array<string, mixed> $client
     */
    private function redirectAllowed(array $client, string $redirectUri): bool
    {
        $registered = $client['redirect_uris'] ?? null;
        if (! is_array($registered)
            || ! in_array($redirectUri, $registered, true)
            || ! $this->validRedirectUri($redirectUri)) {
            return false;
        }

        return true;
    }

    private function validRedirectUri(string $redirectUri): bool
    {
        if (strlen($redirectUri) > 2048 || filter_var($redirectUri, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        $parts = parse_url($redirectUri);
        if (! is_array($parts)
            || isset($parts['user'])
            || isset($parts['pass'])
            || isset($parts['query'])
            || isset($parts['fragment'])
            || empty($parts['host'])
            || empty($parts['scheme'])) {
            return false;
        }

        if (strtolower($parts['scheme']) === 'https') {
            return true;
        }

        if (! $this->allowInsecureLoopback || strtolower($parts['scheme']) !== 'http') {
            return false;
        }

        return in_array(strtolower($parts['host']), ['127.0.0.1', '::1', 'localhost'], true);
    }
}
