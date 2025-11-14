<?php

namespace App\Http\Controllers\API\Settings;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\SystemSettings;
use App\System\Command;
use Illuminate\Http\Request;

class TweaksController extends Controller
{
    /**
     * Get existing configuration
     *
     * @return JsonResponse
     */
    public function getConfiguration()
    {
        return response()->json([
            'APP_LANG' => env('APP_LANG'),
            'APP_NOTIFICATION_EMAIL' => env('APP_NOTIFICATION_EMAIL'),
            'APP_URL' => env('APP_URL'),
            'EXTENSION_TIMEOUT' => env('EXTENSION_TIMEOUT', 30),
            'APP_DEBUG' => (bool) env('APP_DEBUG', 'false'),
            'EXTENSION_DEVELOPER_MODE' => (bool) env('EXTENSION_DEVELOPER_MODE', 'false'),
            'NEW_LOG_LEVEL' => env('NEW_LOG_LEVEL'),
            'LDAP_IGNORE_CERT' => (bool) env('LDAP_IGNORE_CERT', 'false'),
            'LOGIN_IMAGE' => SystemSettings::where('key', 'LOGIN_IMAGE')->first()?->data ?? '',
            'DEFAULT_AUTH_GATE' => env('DEFAULT_AUTH_GATE', 'liman'),
            'JWT_TTL' => env('JWT_TTL', 120),
            'CORS_TRUSTED_ORIGINS' => env('CORS_TRUSTED_ORIGINS', ''),
            'LOGOUT_REDIRECT_URL' => env('LOGOUT_REDIRECT_URL', ''),
        ]);
    }

    /**
     * Save new mail configuration
     *
     * @param Request $request
     * @return JsonResponse
     * @throws GuzzleException
     */
    public function saveConfiguration(Request $request)
    {
        validate([
            'APP_LANG' => 'required|string',
            'APP_NOTIFICATION_EMAIL' => 'required|email',
            'APP_URL' => 'required|url',
            'EXTENSION_TIMEOUT' => 'required|integer|min:1|max:300',
            'NEW_LOG_LEVEL' => 'required|string',
            'DEFAULT_AUTH_GATE' => 'required|string|in:liman,keycloak,ldap',
            'JWT_TTL' => 'required|integer|min:15|max:999999',
            'CORS_TRUSTED_ORIGINS' => 'nullable|string',
            'LOGOUT_REDIRECT_URL' => 'nullable|url',
        ], [], [
            "EXTENSION_TIMEOUT" => "Eklenti zaman aşımı"
        ]);

        setEnv([
            'APP_LANG' => $request->APP_LANG,
            'APP_NOTIFICATION_EMAIL' => $request->APP_NOTIFICATION_EMAIL,
            'APP_URL' => $request->APP_URL,
            'EXTENSION_TIMEOUT' => $request->EXTENSION_TIMEOUT,
            'APP_DEBUG' => (bool) $request->APP_DEBUG,
            'EXTENSION_DEVELOPER_MODE' => (bool) $request->EXTENSION_DEVELOPER_MODE,
            'NEW_LOG_LEVEL' => $request->NEW_LOG_LEVEL,
            'LDAP_IGNORE_CERT' => (bool) $request->LDAP_IGNORE_CERT,
            'DEFAULT_AUTH_GATE' => $request->DEFAULT_AUTH_GATE,
            'JWT_TTL' => $request->JWT_TTL,
            'CORS_TRUSTED_ORIGINS' => $request->CORS_TRUSTED_ORIGINS,
            'LOGOUT_REDIRECT_URL' => $request->LOGOUT_REDIRECT_URL,
        ]);

        if ($request->has('LOGIN_IMAGE') && $request->LOGIN_IMAGE != '') {
            // Control if LOGIN_IMAGE is bigger than 1mb
            if (strlen($request->LOGIN_IMAGE) > 1048576) {
                return response()->json([
                    'LOGIN_IMAGE' => 'Giriş arka planı resmi 1MB\'dan büyük olamaz.',
                ], 422);
            }

            // Validate image format and prevent SVG/script injection
            $imageValidation = $this->validateBase64Image($request->LOGIN_IMAGE);
            if (!$imageValidation['valid']) {
                return response()->json([
                    'LOGIN_IMAGE' => $imageValidation['error'],
                ], 422);
            }

            SystemSettings::updateOrCreate(
                ['key' => 'LOGIN_IMAGE'],
                ['data' => $request->get('LOGIN_IMAGE')]
            );
        }

        AuditLog::write(
            'tweak',
            'edit',
            [
                'APP_LANG' => $request->APP_LANG,
                'APP_NOTIFICATION_EMAIL' => $request->APP_NOTIFICATION_EMAIL,
                'APP_URL' => $request->APP_URL,
                'EXTENSION_TIMEOUT' => $request->EXTENSION_TIMEOUT,
                'APP_DEBUG' => (bool) $request->APP_DEBUG,
                'EXTENSION_DEVELOPER_MODE' => (bool) $request->EXTENSION_DEVELOPER_MODE,
                'NEW_LOG_LEVEL' => $request->NEW_LOG_LEVEL,
                'LDAP_IGNORE_CERT' => (bool) $request->LDAP_IGNORE_CERT,
                'DEFAULT_AUTH_GATE' => $request->DEFAULT_AUTH_GATE,
                'JWT_TTL' => $request->JWT_TTL,
                'CORS_TRUSTED_ORIGINS' => $request->CORS_TRUSTED_ORIGINS,
                'LOGOUT_REDIRECT_URL' => $request->LOGOUT_REDIRECT_URL,
            ],
            "TWEAK_EDIT"
        );

        Command::runSystem('systemctl restart liman-render');

        return response()->json([
            'message' => 'Ayarlar kaydedildi.',
        ]);
    }

    /**
     * Validate base64 encoded image data
     * Prevents SVG injection and validates image format
     *
     * @param string $base64Data
     * @return array
     */
    private function validateBase64Image($base64Data)
    {
        // Allowed MIME types (excluding SVG for security)
        $allowedMimeTypes = [
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/gif',
            'image/webp',
        ];

        // Extract MIME type from base64 data URL
        if (preg_match('/^data:(image\/[a-zA-Z0-9\+\-\.]+);base64,/', $base64Data, $matches)) {
            $mimeType = $matches[1];
            
            // Check if MIME type is allowed
            if (!in_array($mimeType, $allowedMimeTypes)) {
                return [
                    'valid' => false,
                    'error' => 'Sadece JPG, PNG, GIF ve WebP formatları desteklenmektedir. SVG dosyaları güvenlik nedeniyle kabul edilmemektedir.',
                ];
            }

            // Extract base64 data
            $base64String = preg_replace('/^data:image\/[a-zA-Z0-9\+\-\.]+;base64,/', '', $base64Data);
            $imageData = base64_decode($base64String, true);

            // Validate base64 decoding
            if ($imageData === false) {
                return [
                    'valid' => false,
                    'error' => 'Geçersiz base64 resim verisi.',
                ];
            }

            // Verify actual image data using getimagesizefromstring
            $imageInfo = @getimagesizefromstring($imageData);
            if ($imageInfo === false) {
                return [
                    'valid' => false,
                    'error' => 'Resim verisi doğrulanamadı. Lütfen geçerli bir resim dosyası yükleyin.',
                ];
            }

            // Verify MIME type matches actual image type
            $actualMimeType = $imageInfo['mime'];
            if ($actualMimeType !== $mimeType) {
                return [
                    'valid' => false,
                    'error' => 'Resim formatı eşleşmiyor. Dosya içeriği ile MIME tipi tutarsız.',
                ];
            }

            // Additional check: ensure it's not an SVG disguised as another format
            if (strpos($imageData, '<svg') !== false || strpos($imageData, '<?xml') !== false) {
                return [
                    'valid' => false,
                    'error' => 'SVG içeriği tespit edildi. Güvenlik nedeniyle SVG dosyaları kabul edilmemektedir.',
                ];
            }

            return [
                'valid' => true,
                'mimeType' => $mimeType,
                'width' => $imageInfo[0],
                'height' => $imageInfo[1],
            ];
        }

        return [
            'valid' => false,
            'error' => 'Geçersiz resim formatı. Base64 kodlanmış resim bekleniyor.',
        ];
    }
}
