<?php

namespace App\Classes\Authentication\OIDC;

use App\Classes\Authentication\Authenticator;
use App\Classes\Authentication\Handoff\AuthenticationHandoffService;
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

    private const STATE_LOCK_PREFIX = 'oidc_state_lock:';

    private const STATE_TTL = 1800; // 30 dakika

    public function __construct(
        ?OpenIDConnectClient $client = null,
        ?OIDCUserProvisioner $userProvisioner = null,
        ?OIDCRoleMapper $roleMapper = null,
        ?OIDCTokenStore $tokenStore = null,
        ?AuthenticationHandoffService $handoffService = null,
    ) {
        $this->client = $client ?? new OpenIDConnectClient;
        $this->userProvisioner = $userProvisioner ?? new OIDCUserProvisioner;
        $this->roleMapper = $roleMapper ?? new OIDCRoleMapper;
        $this->tokenStore = $tokenStore ?? new OIDCTokenStore;
        $this->handoffService = $handoffService ?? new AuthenticationHandoffService;
    }

    /** @var OpenIDConnectClient */
    private $client;

    /** @var OIDCUserProvisioner */
    private $userProvisioner;

    /** @var OIDCRoleMapper */
    private $roleMapper;

    /** @var OIDCTokenStore */
    private $tokenStore;

    private AuthenticationHandoffService $handoffService;

    /**
     * OIDC flow'unu başlat - frontend'e redirect URL'i döndür.
     */
    public function initiate(Request $request): JsonResponse
    {
        $state = Str::random(40);
        $nonce = Str::random(32);

        $handoff = null;
        if ($request->has('handoff')) {
            $input = $request->input('handoff');
            $handoff = is_array($input)
                ? $this->handoffService->authorizeInitiation($input)
                : null;
            if ($handoff === null) {
                return $this->error('Invalid authentication handoff request', 400);
            }
        }

        $redirectPath = null;
        if ($request->has('redirect_path')) {
            $redirectPath = $this->validateRedirectPath($request->input('redirect_path'));
        }

        Cache::put(self::STATE_CACHE_PREFIX.$state, [
            'nonce' => $nonce,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'redirect_path' => $redirectPath,
            'handoff' => $handoff,
            'created_at' => now()->toDateTimeString(),
        ], self::STATE_TTL);

        $authUrl = $this->client->buildAuthorizationUrl($state, $nonce);

        Log::info('OIDC flow initiated', [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'handoff_client_id' => $handoff['client_id'] ?? null,
        ]);

        return response()->json([
            'message' => 'OIDC provider\'a yönlendiriliyor...',
            'redirect_required' => true,
            'redirect_url' => $authUrl,
        ])->withHeaders([
            'Cache-Control' => 'no-store',
            'Pragma' => 'no-cache',
            'Referrer-Policy' => 'no-referrer',
        ]);
    }

    /**
     * OIDC callback'ini handle et.
     */
    public function handleCallback(Request $request): JsonResponse|RedirectResponse
    {
        $stateData = null;

        try {
            Log::info('OIDC callback received', [
                'has_state' => $request->has('state'),
                'has_code' => $request->has('code'),
                'has_error' => $request->has('error'),
                'ip' => $request->ip(),
            ]);

            if (! $request->has('state')) {
                Log::error('OIDC callback received without state parameter');

                return $this->error('State parameter not received', 400);
            }

            $stateData = $this->consumeState((string) $request->state);
            if (! $stateData) {
                Log::error('OIDC state not found in cache', [
                    'ip' => $request->ip(),
                ]);

                return $this->error('Invalid or expired state parameter', 400);
            }

            if ($request->has('error')) {
                Log::error('OIDC authentication error', [
                    'error' => (string) $request->error,
                ]);

                return $this->callbackError(
                    $stateData,
                    'access_denied',
                    'OIDC authentication failed',
                    401,
                );
            }

            if (! $request->has('code')) {
                Log::error('OIDC callback received without authorization code');

                return $this->callbackError(
                    $stateData,
                    'invalid_request',
                    'Authorization code not received',
                    400,
                );
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
                return $this->callbackError(
                    $stateData,
                    'invalid_token',
                    'ID token claim validation failed',
                    400,
                );
            }

            $user = $this->userProvisioner->findOrCreate($claims);
            if (! $user) {
                Log::error('OIDC user creation/update failed.');

                return $this->callbackError(
                    $stateData,
                    'server_error',
                    'User creation failed',
                    500,
                );
            }

            auth('api')->factory()->setTTL($user->session_time);

            $request->merge([
                'ip' => $stateData['ip'],
                'user_agent' => $stateData['user_agent'],
            ]);
            if (empty($stateData['handoff'])) {
                $request->merge(['callback_url' => $request->fullUrl()]);
            }

            $permissions = $this->extractPermissions($tokenResponse, $claims);
            $this->roleMapper->assignByPermissions($user, $permissions);

            $externalToken = $this->extractExternalToken($claims);
            $this->tokenStore->persist($user, $tokenResponse, $externalToken, $permissions);

            Log::info('OIDC authentication successful', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            $limanToken = auth('api')->login($user);
            if (! empty($stateData['handoff']) && is_array($stateData['handoff'])) {
                $tokenPayload = Authenticator::createHandoffToken($limanToken, $request);
                $code = $this->handoffService->issue($stateData['handoff'], $tokenPayload);

                return redirect()->away(
                    $this->handoffService->successRedirect($stateData['handoff'], $code),
                )->withHeaders([
                    'Cache-Control' => 'no-store',
                    'Pragma' => 'no-cache',
                    'Referrer-Policy' => 'no-referrer',
                ]);
            }

            $limanTokenResponse = Authenticator::createNewToken($limanToken, $request);

            return redirect($stateData['redirect_path'] ?? '/')
                ->withCookies($limanTokenResponse->headers->getCookies());
        } catch (OpenIDConnectClientException $e) {
            Log::error('OIDC authentication failed', [
                'exception' => get_class($e),
            ]);

            return $this->callbackError(
                $stateData,
                'invalid_grant',
                'Authentication failed',
                400,
            );
        } catch (\Exception $e) {
            Log::error('OIDC authentication exception', [
                'exception' => get_class($e),
            ]);

            return $this->callbackError(
                $stateData,
                'server_error',
                'Authentication failed',
                500,
            );
        }
    }

    /**
     * Consume OIDC state once while holding a distributed cache lock.
     *
     * @return array<string, mixed>|null
     */
    private function consumeState(string $state): ?array
    {
        if (! preg_match('/\A[A-Za-z0-9]{40}\z/', $state)) {
            return null;
        }

        $lock = Cache::lock(self::STATE_LOCK_PREFIX.hash('sha256', $state), 5);
        if (! $lock->get()) {
            return null;
        }

        try {
            $value = Cache::pull(self::STATE_CACHE_PREFIX.$state);

            return is_array($value) ? $value : null;
        } finally {
            try {
                $lock->release();
            } catch (\Throwable $e) {
                Log::warning('OIDC state lock release failed');
            }
        }
    }

    /**
     * Return browser handoff errors to the registered application without
     * exposing provider details. Native Liman OIDC retains its JSON behavior.
     *
     * @param array<string, mixed>|null $stateData
     */
    private function callbackError(
        ?array $stateData,
        string $error,
        string $message,
        int $status,
    ): JsonResponse|RedirectResponse {
        $handoff = $stateData['handoff'] ?? null;
        if (is_array($handoff)) {
            return redirect()->away(
                $this->handoffService->errorRedirect($handoff, $error),
            )->withHeaders([
                'Cache-Control' => 'no-store',
                'Pragma' => 'no-cache',
                'Referrer-Policy' => 'no-referrer',
            ]);
        }

        return $this->error($message, $status);
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
