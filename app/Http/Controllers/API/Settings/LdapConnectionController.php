<?php

namespace App\Http\Controllers\API\Settings;

use App\Classes\Ldap;
use App\Classes\LDAPSearchOptions;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LdapConnectionController extends Controller
{
    public function getConfiguration()
    {
        return response()->json([
            'active' => (bool) env('LDAP_STATUS'),
            'server_address' => env('LDAP_HOST'),
            'objectguid' => env('LDAP_GUID_COLUMN'),
            'mail' => env('LDAP_MAIL_COLUMN'),
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
