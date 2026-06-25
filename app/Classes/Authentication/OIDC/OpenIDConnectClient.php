<?php

namespace App\Classes\Authentication\OIDC;

use Jumbojett\OpenIDConnectClient as BaseOpenIDConnectClient;
use Jumbojett\OpenIDConnectClientException;

/**
 * Liman'ın stateless iki adımlı OIDC akışına (JSON redirect URL + ayrı callback
 * route) uyum sağlamak için Jumbojett OpenIDConnectClient'ı saran adapter.
 *
 * - PHP session kullanmaz; state/nonce Liman Cache'inde tutulur. Bu yüzden
 *   session metodları in-memory store'a yönlendirilir.
 *   {@see self::completeAuthorizationCodeFlow()} çağrılmadan önce nonce bu
 *   store'a yazılır.
 * - Doğrudan HTTP redirect yapmaz; bunun yerine
 *   {@see self::buildAuthorizationUrl()} URL döndürür.
 *
 * İmza doğrulama kapsamı (Jumbojett'in kendisi):
 *   RS256/384/512, PS256/512  -> JWKS public key
 *   HS256/384/512             -> client_secret (güvenli routing, alg-confusion yok)
 */
class OpenIDConnectClient extends BaseOpenIDConnectClient
{
    /** @var array<string, mixed> PHP session yerine kullanılan in-memory store */
    private array $memorySession = [];

    /**
     * OIDC env yapılandırmasından client oluştur.
     */
    public function __construct()
    {
        $issuer = rtrim((string) env('OIDC_ISSUER_URL'), '/');

        parent::__construct(
            $issuer ?: null,
            (string) env('OIDC_CLIENT_ID'),
            (string) env('OIDC_CLIENT_SECRET'),
            $issuer ?: null,
        );

        $redirectUri = env('OIDC_REDIRECT_URI');
        if ($redirectUri) {
            $this->setRedirectURL($redirectUri);
        }

        $this->addScope(['profile', 'email']);

        $this->configureExplicitEndpoints();

        // Issuer kontrolünü trailing slash normalize ederek yap.
        $this->setIssuerValidator(fn ($iss) => rtrim((string) $iss, '/') === $issuer);
    }

    /**
     * .well-known discovery zorunlu olmasın diye elle girilmiş endpoint'leri
     * provider config'e yaz. Yoksa library auto-discovery'e düşer.
     */
    private function configureExplicitEndpoints(): void
    {
        $params = [];

        $authEndpoint = env('OIDC_AUTH_ENDPOINT');
        if ($authEndpoint) {
            $params['authorization_endpoint'] = $this->resolveEndpoint($authEndpoint);
        }

        $tokenEndpoint = env('OIDC_TOKEN_ENDPOINT');
        if ($tokenEndpoint) {
            $params['token_endpoint'] = $this->resolveEndpoint($tokenEndpoint);
        }

        $userinfoEndpoint = env('OIDC_USERINFO_ENDPOINT');
        if ($userinfoEndpoint) {
            $params['userinfo_endpoint'] = $this->resolveEndpoint($userinfoEndpoint);
        }

        $jwksUri = env('OIDC_JWKS_URI');
        if ($jwksUri) {
            $params['jwks_uri'] = $this->resolveEndpoint($jwksUri);
        }

        if (! empty($params)) {
            $this->providerConfigParam($params);
        }
    }

    /**
     * Göreli (ör. "/oauth/token") veya tam URL'li endpoint değerlerini mutlak
     * URL'ye çevir.
     */
    private function resolveEndpoint(string $endpoint): string
    {
        if (preg_match('#^https?://#i', $endpoint)) {
            return $endpoint;
        }

        return rtrim((string) env('OIDC_ISSUER_URL'), '/').'/'.ltrim($endpoint, '/');
    }

    /**
     * Session metodlarını in-memory store'a yönlendir (stateless API).
     */
    protected function setSessionKey(string $key, $value): void
    {
        $this->memorySession[$key] = $value;
    }

    protected function getSessionKey(string $key)
    {
        return array_key_exists($key, $this->memorySession) ? $this->memorySession[$key] : false;
    }

    protected function unsetSessionKey(string $key): void
    {
        unset($this->memorySession[$key]);
    }

    protected function startSession(): void
    {
        // no-op - PHP session kullanılmıyor
    }

    protected function commitSession(): void
    {
        // no-op
    }

    /**
     * Doğrudan HTTP redirect + exit'i devre dışı bırak.
     * Liman akışı URL'yi JSON ile frontend'e döndürür.
     *
     * @return never
     */
    public function redirect(string $url)
    {
        throw new OpenIDConnectClientException(
            'Direct redirect is disabled in stateless mode; use buildAuthorizationUrl() instead.'
        );
    }

    /**
     * Authorization endpoint URL'sini, HTTP redirect yapmadan döndür.
     * State ve nonce Liman tarafından üretilip Cache'e yazılır; bu yüzden
     * library'nin session tabanlı state/nonce üretimine güvenilmez.
     */
    public function buildAuthorizationUrl(string $state, string $nonce): string
    {
        $authEndpoint = $this->getProviderConfigValue('authorization_endpoint');

        $params = array_merge($this->getAuthParams(), [
            'response_type' => 'code',
            'redirect_uri' => $this->getRedirectURL(),
            'client_id' => $this->getClientID(),
            'nonce' => $nonce,
            'state' => $state,
            'scope' => implode(' ', array_merge($this->getScopes(), ['openid'])),
        ]);

        $responseTypes = $this->getResponseTypes();
        if (! empty($responseTypes)) {
            $params['response_type'] = implode(' ', $responseTypes);
        }

        return $authEndpoint.(str_contains($authEndpoint, '?') ? '&' : '?')
            .http_build_query($params, '', '&');
    }

    /**
     * Authorization code'u token'a çevir, ID token'ı JWKS/client_secret ile
     * doğrula ve claim'leri döndür.
     *
     * @return array{claims: object, token_response: object}
     *
     * @throws OpenIDConnectClientException
     */
    public function completeAuthorizationCodeFlow(string $code, string $nonce): array
    {
        // verifyJWTClaims nonce kontrolü için in-memory store'a yazıyoruz.
        $this->setSessionKey('openid_connect_nonce', $nonce);

        $tokenResponse = $this->requestTokens($code);

        if (isset($tokenResponse->error)) {
            throw new OpenIDConnectClientException(
                $tokenResponse->error_description ?? ('Token exchange failed: '.$tokenResponse->error)
            );
        }

        if (! property_exists($tokenResponse, 'id_token')) {
            throw new OpenIDConnectClientException('User did not authorize openid scope.');
        }

        $idToken = $tokenResponse->id_token;
        $headers = $this->decodeJWT($idToken);
        if (isset($headers->enc)) {
            $idToken = $this->handleJweResponse($idToken);
        }

        $claims = $this->decodeJWT($idToken, 1);

        // İmzayı JWKS (asimetrik) veya client_secret (HS*) ile doğrula.
        $this->verifySignatures($idToken);

        // getIdTokenPayload()/getIdTokenHeader() claim kontrolünde kullanılıyor.
        $this->setIdToken($idToken);

        if (! $this->verifyJWTClaims($claims, $tokenResponse->access_token ?? null)) {
            throw new OpenIDConnectClientException('Unable to verify JWT claims');
        }

        $this->verifiedClaims = $claims;
        $this->accessToken = $tokenResponse->access_token ?? null;

        return ['claims' => $claims, 'token_response' => $tokenResponse];
    }
}
