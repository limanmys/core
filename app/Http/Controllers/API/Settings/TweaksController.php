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
        ]);

        if ($request->has('LOGIN_IMAGE') && $request->LOGIN_IMAGE != '') {
            // Control if LOGIN_IMAGE is bigger than 1mb
            if (strlen($request->LOGIN_IMAGE) > 1048576) {
                return response()->json([
                    'LOGIN_IMAGE' => 'Giriş arka planı resmi 1MB\'dan büyük olamaz.',
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
            ],
            "TWEAK_EDIT"
        );

        Command::runSystem('systemctl restart liman-render');

        return response()->json([
            'message' => 'Ayarlar kaydedildi.',
        ]);
    }
}
