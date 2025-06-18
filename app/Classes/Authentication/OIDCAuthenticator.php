<?php

namespace App\Classes\Authentication;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OIDCAuthenticator implements AuthenticatorInterface
{
    public function authenticate($credentials, $request): JsonResponse
    {
        return $this->initiateOIDCFlow($request);
    }
    
    /**
     * OIDC flow'unu başlat - frontend'e redirect URL'i döndür
     */
    private function initiateOIDCFlow($request): JsonResponse
    {
        $state = Str::random(40);
        $nonce = Str::random(32);
        
        // State ve nonce'u cache'te sakla (30 dakika TTL)
        Cache::put("oidc_state:{$state}", [
            'nonce' => $nonce,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now()->toDateTimeString()
        ], 30 * 60); // 30 dakika
        
        $params = http_build_query([
            'client_id' => env('OIDC_CLIENT_ID'),
            'redirect_uri' => env('OIDC_REDIRECT_URI'),
            'response_type' => 'code',
            'scope' => 'openid profile email',
            'state' => $state,
            'nonce' => $nonce,
        ]);
        
        $authEndpoint = env('OIDC_AUTH_ENDPOINT', '/authorize');
        $authUrl = env('OIDC_ISSUER_URL') . $authEndpoint . '?' . $params;
        
        Log::info("OIDC flow initiated", [
            'state' => $state,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);
        
        return response()->json([
            'message' => 'OIDC provider\'a yönlendiriliyor...',
            'redirect_required' => true,
            'redirect_url' => $authUrl
        ]);
    }
    
    /**
     * OIDC callback'ini handle et
     */
    public static function handleCallback($request): JsonResponse|RedirectResponse
    {
        try {
            Log::info("OIDC callback received", [
                'state' => $request->state,
                'has_code' => $request->has('code'),
                'has_error' => $request->has('error'),
                'ip' => $request->ip()
            ]);

            // Error check
            if ($request->has('error')) {
                Log::error('OIDC authentication error: ' . $request->error . ' - ' . $request->error_description);
                return response()->json([
                    'error' => true,
                    'message' => 'OIDC authentication failed: ' . ($request->error_description ?? $request->error)
                ], 401);
            }

            // Authorization code kontrolü
            if (!$request->has('code')) {
                Log::error('OIDC callback received without authorization code');
                return response()->json([
                    'error' => true,
                    'message' => 'Authorization code not received'
                ], 400);
            }

            // State parametresi var mı?
            if (!$request->has('state')) {
                Log::error('OIDC callback received without state parameter');
                return response()->json([
                    'error' => true,
                    'message' => 'State parameter not received'
                ], 400);
            }
            
            // Cache'ten state bilgilerini al
            $stateData = Cache::get("oidc_state:{$request->state}");
            
            if (!$stateData) {
                Log::error('OIDC state not found in cache', [
                    'state' => $request->state,
                    'ip' => $request->ip()
                ]);
                return response()->json([
                    'error' => true,
                    'message' => 'Invalid or expired state parameter'
                ], 400);
            }

            Log::info("OIDC state found in cache", [
                'state' => $request->state,
                'cached_data' => $stateData
            ]);
            
            // Authorization code ile token exchange
            $tokenEndpoint = env('OIDC_TOKEN_ENDPOINT', '/oauth/token');
            $tokenResponse = Http::asForm()->post(env('OIDC_ISSUER_URL') . $tokenEndpoint, [
                'grant_type' => 'authorization_code',
                'client_id' => env('OIDC_CLIENT_ID'),
                'client_secret' => env('OIDC_CLIENT_SECRET'),
                'redirect_uri' => env('OIDC_REDIRECT_URI'),
                'code' => $request->code,
            ]);
            
            if (!$tokenResponse->successful()) {
                Log::error('OIDC token exchange failed: ' . $tokenResponse->body());
                return response()->json([
                    'error' => true,
                    'message' => 'Token exchange failed'
                ], 400);
            }
            
            $tokenData = $tokenResponse->json();
            
            // ID token'ı decode et ve verify et
            $userInfo = self::decodeAndVerifyIdToken($tokenData['id_token']);
            
            if (!$userInfo) {
                Log::error('OIDC ID token verification failed.');
                return response()->json([
                    'error' => true,
                    'message' => 'ID token verification failed'
                ], 400);
            }
            
            // Nonce kontrolü
            if (isset($userInfo['nonce']) && $userInfo['nonce'] !== $stateData['nonce']) {
                Log::error('OIDC authentication failed. Invalid nonce.', [
                    'token_nonce' => $userInfo['nonce'],
                    'cached_nonce' => $stateData['nonce']
                ]);
                return response()->json([
                    'error' => true,
                    'message' => 'Invalid nonce'
                ], 400);
            }
            
            // User'ı bul veya oluştur
            $user = self::findOrCreateUser($userInfo);
            
            if (!$user) {
                Log::error('OIDC user creation/update failed.');
                return response()->json([
                    'error' => true,
                    'message' => 'User creation failed'
                ], 500);
            }
            
            
            // User'ın session time'ını set et
            auth('api')->factory()->setTTL($user->session_time);
            
            // Request objesi için orijinal IP ve User-Agent bilgilerini kullan
            $request->merge([
                'ip' => $stateData['ip'],
                'user_agent' => $stateData['user_agent'],
                'callback_url' => $request->fullUrl() // Callback URL'yi ekle
            ]);
            
            Log::info("OIDC authentication successful", [
                'user_id' => $user->id,
                'email' => $user->email
            ]);

            // Cache'ten state'i sil
            Cache::forget("oidc_state:{$request->state}");

            // JWT token oluştur ve cookie'ye set et
            return Authenticator::createNewToken(
                auth('api')->login($user),
                $request
            );
        } catch (\Exception $e) {
            Log::error('OIDC authentication exception: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => true,
                'message' => 'Authentication failed'
            ], 500);
        }
    }
    
    /**
     * ID token'ı decode et ve verify et
     */
    private static function decodeAndVerifyIdToken($idToken): ?array
    {
        try {
            // JWT token'ı parçala
            $parts = explode('.', $idToken);
            if (count($parts) !== 3) {
                Log::error('Invalid JWT token format');
                return null;
            }
            
            // Header ve payload'ı decode et
            $header = json_decode(base64_decode(str_pad(strtr($parts[0], '-_', '+/'), strlen($parts[0]) % 4, '=', STR_PAD_RIGHT)), true);
            $payload = json_decode(base64_decode(str_pad(strtr($parts[1], '-_', '+/'), strlen($parts[1]) % 4, '=', STR_PAD_RIGHT)), true);
            
            Log::info('JWT payload decoded', [
                'payload' => $payload
            ]);
            
            // Basic validation
            if (!$payload || !isset($payload['sub']) || !isset($payload['email'])) {
                Log::error('OIDC ID token missing required claims', [
                    'has_sub' => isset($payload['sub']),
                    'has_email' => isset($payload['email']),
                    'payload' => $payload
                ]);
                return null;
            }
            
            // Issuer kontrolü (trailing slash normalize edilerek)
            $expectedIssuer = rtrim(env('OIDC_ISSUER_URL'), '/');
            $actualIssuer = rtrim($payload['iss'], '/');
            
            if ($actualIssuer !== $expectedIssuer) {
                Log::error('OIDC ID token issuer mismatch', [
                    'expected' => $expectedIssuer,
                    'actual' => $actualIssuer,
                    'original_expected' => env('OIDC_ISSUER_URL'),
                    'original_actual' => $payload['iss']
                ]);
                return null;
            }
            
            // Audience kontrolü
            if ($payload['aud'] !== env('OIDC_CLIENT_ID')) {
                Log::error('OIDC ID token audience mismatch', [
                    'expected' => env('OIDC_CLIENT_ID'),
                    'actual' => $payload['aud']
                ]);
                return null;
            }
            
            // Expiration kontrolü
            if ($payload['exp'] < time()) {
                Log::error('OIDC ID token expired', [
                    'exp' => $payload['exp'],
                    'now' => time()
                ]);
                return null;
            }
            
            return $payload;
            
        } catch (\Exception $e) {
            Log::error('ID token decode failed: ' . $e->getMessage());
            return null;
        }
    }
     /**
     * User'ı bul veya oluştur
     */
    private static function findOrCreateUser($userInfo): ?User
    {
        try {
            // OIDC sub ID'si ile user'ı ara
            $user = User::where('oidc_sub', $userInfo['sub'])->first();
            
            if (!$user) {
                // Email ile user'ı ara
                $user = User::where('email', strtolower($userInfo['email']))->first();
                
                if ($user) {
                    // Mevcut user'a OIDC sub'ı ekle
                    $user->update([
                        'oidc_sub' => $userInfo['sub'],
                        'auth_type' => 'oidc',
                        'name' => $userInfo['name'] ?? $userInfo['preferred_username'] ?? $userInfo['nickname'] ?? $userInfo['email'],
                    ]);
                } else {
                    // Database transaction içinde yeni user oluştur
                    $user = DB::transaction(function () use ($userInfo) {
                        $newUser = User::create([
                            'oidc_sub' => $userInfo['sub'],
                            'name' => $userInfo['name'] ?? $userInfo['preferred_username'] ?? $userInfo['nickname'] ?? $userInfo['email'],
                            'email' => strtolower($userInfo['email']),
                            'username' => $userInfo['preferred_username'] ?? explode('@', $userInfo['email'])[0],
                            'auth_type' => 'oidc',
                            'password' => Hash::make(Str::random(32)), // Random password
                            'forceChange' => false,
                        ]);
                        
                        // User'ın gerçekten kaydedildiğini ve ID'sinin olduğunu kontrol et
                        $newUser->refresh();
                        if (!$newUser->id) {
                            throw new \Exception('User creation failed - ID is null');
                        }
                        
                        return $newUser;
                    });
                }
            } else {
                // Mevcut OIDC user'ı güncelle
                $user->update([
                    'name' => $userInfo['name'] ?? $userInfo['preferred_username'] ?? $userInfo['nickname'] ?? $userInfo['email'],
                    'email' => strtolower($userInfo['email']),
                    'auth_type' => 'oidc',
                ]);
            }
            
            // Son kontrol: User'ın JWT identifier'ının olduğunu garanti et
            if (!$user->getJWTIdentifier()) {
                Log::error('User JWT identifier is null', [
                    'user_id' => $user->id,
                    'user_exists' => $user->exists,
                ]);
                return null;
            }
            
            return $user;
            
        } catch (\Exception $e) {
            Log::error('User creation/update failed: ' . $e->getMessage());
            return null;
        }
    }
}