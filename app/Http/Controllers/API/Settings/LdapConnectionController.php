<?php

namespace App\Http\Controllers\API\Settings;

use App\Classes\Ldap;
use App\Http\Controllers\Controller;
use App\Models\Certificate;
use Illuminate\Http\Request;

class LdapConnectionController extends Controller
{
    public function getConfiguration()
    {
        return response()->json([
            'active' => (bool) env('LDAP_STATUS', 'false'),
            'server_address' => env('LDAP_HOST'),
            'objectguid' => env('LDAP_GUID_COLUMN', 'objectguid'),
            'mail' => env('LDAP_MAIL_COLUMN', 'mail'),
        ]);
    }

    public function saveConfiguration(Request $request)
    {
        $cert = Certificate::where([
            'server_hostname' => $request->server_address,
            'origin' => 636,
        ])->first();
        if (! $cert) {
            [$flag, $message] = retrieveCertificate(
                $request->server_address,
                636
            );
            if ($flag) {
                addCertificate($request->server_address, 636, $message['path']);
            }
        }
        if (! setBaseDn($request->server_address)) {
            return response()->json([
                'status' => false,
                'message' => 'LDAP bağlantısı başarısız.',
            ], 500);
        }
        setEnv([
            'LDAP_HOST' => $request->server_address,
            'LDAP_GUID_COLUMN' => $request->objectguid,
            'LDAP_STATUS' => (bool) $request->active,
            'LDAP_MAIL_COLUMN' => $request->mail,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Ayarlar kaydedildi.',
        ]);
    }

    public function auth(Request $request)
    {
        validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        try {
            $ldap = new Ldap(
                env('LDAP_HOST'),
                $request->username,
                $request->password,
            );
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'LDAP bağlantısı başarısız.',
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'LDAP bağlantısı başarılı.',
        ]);
    }
}