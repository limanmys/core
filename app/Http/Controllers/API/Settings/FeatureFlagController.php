<?php

namespace App\Http\Controllers\API\Settings;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\SystemSettings;
use Illuminate\Http\Request;

class FeatureFlagController extends Controller
{
    private const SETTINGS_KEY = 'FEATURE_FLAGS';

    private const DEFAULT_FLAGS = [
        'lang_tr' => true,
        'lang_en' => true,
        'lang_de' => true,
        'settings_vault' => true,
        'settings_tokens' => true,
        'settings_extensions' => true,
        'settings_users' => true,
        'settings_roles' => true,
        'settings_email' => true,
        'settings_external_notifications' => true,
        'settings_subscriptions' => true,
        'settings_health' => true,
        'server_services' => true,
        'server_packages' => true,
        'server_updates' => true,
        'server_user_management' => true,
        'server_open_ports' => true,
        'server_access_logs' => true,
        'dashboard_most_used_extensions' => true,
        'dashboard_favorite_servers' => true,
        'dashboard_auth_logs' => true,
    ];

    /**
     * Get current feature flags configuration
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getConfiguration()
    {
        $setting = SystemSettings::where('key', self::SETTINGS_KEY)->first();

        if ($setting) {
            $flags = json_decode($setting->data, true);
            // Merge with defaults to include any new flags added later
            $flags = array_merge(self::DEFAULT_FLAGS, $flags);
        } else {
            $flags = self::DEFAULT_FLAGS;
        }

        return response()->json($flags);
    }

    /**
     * Save feature flags configuration
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveConfiguration(Request $request)
    {
        validate([
            'lang_tr' => 'required|boolean',
            'lang_en' => 'required|boolean',
            'lang_de' => 'required|boolean',
            'settings_vault' => 'required|boolean',
            'settings_tokens' => 'required|boolean',
            'settings_extensions' => 'required|boolean',
            'settings_users' => 'required|boolean',
            'settings_roles' => 'required|boolean',
            'settings_email' => 'required|boolean',
            'settings_external_notifications' => 'required|boolean',
            'settings_subscriptions' => 'required|boolean',
            'settings_health' => 'required|boolean',
            'server_services' => 'required|boolean',
            'server_packages' => 'required|boolean',
            'server_updates' => 'required|boolean',
            'server_user_management' => 'required|boolean',
            'server_open_ports' => 'required|boolean',
            'server_access_logs' => 'required|boolean',
            'dashboard_most_used_extensions' => 'required|boolean',
            'dashboard_favorite_servers' => 'required|boolean',
            'dashboard_auth_logs' => 'required|boolean',
        ]);

        $flags = $request->only(array_keys(self::DEFAULT_FLAGS));

        // Ensure at least one language is enabled
        $enabledLangs = array_filter([
            $flags['lang_tr'] ?? false,
            $flags['lang_en'] ?? false,
            $flags['lang_de'] ?? false,
        ]);

        if (count($enabledLangs) === 0) {
            return response()->json([
                'message' => 'En az bir dil aktif olmalıdır.',
            ], 422);
        }

        SystemSettings::updateOrCreate(
            ['key' => self::SETTINGS_KEY],
            ['data' => json_encode($flags)]
        );

        AuditLog::write(
            'feature_flag',
            'edit',
            $flags,
            'FEATURE_FLAG_EDIT'
        );

        return response()->json([
            'message' => 'Özellik bayrakları kaydedildi.',
        ]);
    }
}
