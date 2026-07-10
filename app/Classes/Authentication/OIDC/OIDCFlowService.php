<?php

namespace App\Classes\Authentication\OIDC;

use App\Classes\Authentication\Authenticator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Jumbojett\OpenIDConnectClientException;

/**
 * OIDC akış orkestratörü.
 *
 * İki adımlı stateless akış:
 *   1. {@see self::initiate()} - state/nonce üretir, Cache'e yazar, provider
 *      authorize URL'ini JSON ile frontend'e döndürür.
 *   2. {@see self::handleCallback()} - provider callback'inde code/state alır,
 *      token exchange + ID token doğrulama yapar, kullanıcıyı provisioning eder,
 *      rolleri eşler, token'ı saklar, Liman JWT cookie'si ile redirect eder.
 *
 * Sorumluluklar ayrı collaborator'lara delegation edilmiştir:
 *   {@see OpenIDConnectClient}, {@see OIDCUserProvisioner},
 *   {@see OIDCRoleMapper}, {@see OIDCTokenStore}.
 */
class OIDCFlowService
{
    private const STATE_CACHE_PREFIX = 'oidc_state:';

    private const STATE_TTL = 1800; // 30 dakika

    public function __construct(
        ?OpenIDConnectClient $client = null,
        ?OIDCUserProvisioner $userProvisioner = null,
        ?OIDCRoleMapper $roleMapper = null,
        ?OIDCTokenStore $tokenStore = null,
    ) {
        $this->client = $client ?? new OpenIDConnectClient;
        $this->userProvisioner = $userProvisioner ?? new OIDCUserProvisioner;
        $this->roleMapper = $roleMapper ?? new OIDCRoleMapper;
        $this->tokenStore = $tokenStore ?? new OIDCTokenStore;
    }

    /** @var OpenIDConnectClient */
    private $client;

    /** @var OIDCUserProvisioner */
    private $userProvisioner;

    /** @var OIDCRoleMapper */
    private $roleMapper;

    /** @var OIDCTokenStore */
    private $tokenStore;

    /**
     * OIDC flow'unu başlat - frontend'e redirect URL'i döndür.
     */
    public function initiate(Request $request): JsonResponse
    {
        $state = Str::random(40);
        $nonce = Str::random(32);

        $redirectPath = null;
        if ($request->has('redirect_path')) {
            $redirectPath = $this->validateRedirectPath($request->input('redirect_path'));
        }

        Cache::put(self::STATE_CACHE_PREFIX.$state, [
            'nonce' => $nonce,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'redirect_path' => $redirectPath,
            'created_at' => now()->toDateTimeString(),
        ], self::STATE_TTL);

        $authUrl = $this->client->buildAuthorizationUrl($state, $nonce);

        Log::info('OIDC flow initiated', [
            'state' => $state,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'message' => 'OIDC provider\'a yönlendiriliyor...',
            'redirect_required' => true,
            'redirect_url' => $authUrl,
        ]);
    }

    /**
     * OIDC callback'ini handle et.
     */
    public function handleCallback(Request $request): JsonResponse|RedirectResponse
    {
        try {
            Log::info('OIDC callback received', [
                'state' => $request->state,
                'has_code' => $request->has('code'),
                'has_error' => $request->has('error'),
                'ip' => $request->ip(),
            ]);

            if ($request->has('error')) {
                Log::error('OIDC authentication error: '.$request->error.' - '.$request->error_description);

                return $this->error('OIDC authentication failed: '.($request->error_description ?? $request->error), 401);
            }

            if (! $request->has('code')) {
                Log::error('OIDC callback received without authorization code');

                return $this->error('Authorization code not received', 400);
            }

            if (! $request->has('state')) {
                Log::error('OIDC callback received without state parameter');

                return $this->error('State parameter not received', 400);
            }

            $stateData = Cache::get(self::STATE_CACHE_PREFIX.$request->state);
            if (! $stateData) {
                Log::error('OIDC state not found in cache', [
                    'state' => $request->state,
                    'ip' => $request->ip(),
                ]);

                return $this->error('Invalid or expired state parameter', 400);
            }

            $result = $this->client->completeAuthorizationCodeFlow(
                $request->code,
                $stateData['nonce'],
            );
            $claims = $result['claims'];
            $tokenResponse = $result['token_response'];

            // Jumbojett nonce/issuer/aud/exp/nbf'i doğruladı; ek iat + azp
            // kontrolleri burada (spec: iat gelecekte olmamalı, azp multi-aud'de
            // client_id'ye eşit olmalı).
            if (! $this->validateExtraClaims($claims)) {
                return $this->error('ID token claim validation failed', 400);
            }

            $user = $this->userProvisioner->findOrCreate($claims);
            if (! $user) {
                Log::error('OIDC user creation/update failed.');

                return $this->error('User creation failed', 500);
            }

            auth('api')->factory()->setTTL($user->session_time);

            $request->merge([
                'ip' => $stateData['ip'],
                'user_agent' => $stateData['user_agent'],
                'callback_url' => $request->fullUrl(),
            ]);

            $permissions = $this->extractPermissions($tokenResponse, $claims);
            if (! empty($permissions)) {
                $this->roleMapper->assignByPermissions($user, $permissions);
            }

            $externalToken = $this->extractExternalToken($claims);
            $this->tokenStore->persist($user, $tokenResponse, $externalToken, $permissions);

            Log::info('OIDC authentication successful', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            Cache::forget(self::STATE_CACHE_PREFIX.$request->state);

            $limanTokenResponse = Authenticator::createNewToken(
                auth('api')->login($user),
                $request,
            );

            return redirect($stateData['redirect_path'] ?? '/')
                ->withCookies($limanTokenResponse->headers->getCookies());
        } catch (OpenIDConnectClientException $e) {
            Log::error('OIDC authentication failed: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error('Authentication failed: '.$e->getMessage(), 400);
        } catch (\Exception $e) {
            Log::error('OIDC authentication exception: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error('Authentication failed', 500);
        }
    }

    /**
     * Jumbojett'in verifyJWTClaims'i dışında kalan ek OIDC claim kontrolleri:
     *   - iat gelecekte olmamalı (replay/limit koruması)
     *   - aud birden fazla ise azp, client_id'ye eşit olmalı (OIDC Core §3.1.3.7)
     */
    private function validateExtraClaims(object $claims): bool
    {
        $array = json_decode(json_encode($claims), true) ?: [];

        if (isset($array['iat']) && (int) $array['iat'] > time() + 60) {
            Log::error('OIDC ID token issued in the future', [
                'iat' => $array['iat'],
                'now' => time(),
            ]);

            return false;
        }

        $aud = $array['aud'] ?? null;
        $azp = $array['azp'] ?? null;
        $clientId = env('OIDC_CLIENT_ID');

        if (is_array($aud) && $azp !== null && $azp !== $clientId) {
            Log::error('OIDC ID token azp mismatch', [
                'expected' => $clientId,
                'actual' => $azp,
            ]);

            return false;
        }

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    private function extractPermissions(object $tokenResponse, object $claims): array
    {
        $tokenArray = json_decode(json_encode($tokenResponse), true) ?: [];
        $claimsArray = json_decode(json_encode($claims), true) ?: [];

        return $tokenArray['permissions']
            ?? $claimsArray['permissions']
            ?? [];
    }

    private function extractExternalToken(object $claims): ?string
    {
        $claimsArray = json_decode(json_encode($claims), true) ?: [];

        return $claimsArray['external_token'] ?? null;
    }

    private function error(string $message, int $status): JsonResponse
    {
        return response()->json(['error' => true, 'message' => $message], $status);
    }

    /**
     * Open redirect'i önlemek için redirect_path'i doğrula.
     * Sadece uygulama içi göreli yollara izin verir.
     */
    private function validateRedirectPath(?string $path): ?string
    {
        if (! $path || trim($path) === '') {
            return null;
        }

        $path = trim($path);

        if (preg_match('#^\w+:|^//#', $path)) {
            Log::warning('Rejected redirect_path with protocol', ['path' => $path]);

            return null;
        }

        if (str_contains($path, '@')) {
            Log::warning('Rejected redirect_path with @ symbol', ['path' => $path]);

            return null;
        }

        if (str_contains($path, '\\')) {
            Log::warning('Rejected redirect_path with backslashes', ['path' => $path]);

            return null;
        }

        if (! str_starts_with($path, '/')) {
            Log::warning('Rejected redirect_path not starting with /', ['path' => $path]);

            return null;
        }

        $normalized = preg_replace('#/+#', '/', $path);

        if (preg_match('#\.\.|javascript:|data:|vbscript:#i', $normalized)) {
            Log::warning('Rejected redirect_path with suspicious pattern', ['path' => $path]);

            return null;
        }

        Log::info('Validated redirect_path', ['original' => $path, 'normalized' => $normalized]);

        return $normalized;
    }
}
