<?php

namespace App\Http\Controllers\Server;

use App\Connectors\SSHConnector;
use App\Connectors\SNMPConnector;
use App\Connectors\SSHCertificateConnector;
use App\Connectors\WinRMConnector;
use App\Models\Server;
use App\Http\Controllers\Controller;

class MainController extends Controller
{
    public function checkAccess()
    {
        if (request('port') == -1) {
            return respond("Sunucuya başarıyla erişim sağlandı.", 200);
        }
        $status = @fsockopen(
            request('hostname'),
            request('port'),
            $errno,
            $errstr,
            intval(config('liman.server_connection_timeout')) / 1000
        );
        if (is_resource($status)) {
            return respond("Sunucuya başarıyla erişim sağlandı.", 200);
        } else {
            return respond("Sunucuya erişim sağlanamadı.", 201);
        }
    }

    public function verifyName()
    {
        if (!Server::where('name', request('server_name'))->exists()) {
            return respond("İsim Onaylandı.", 200);
        } else {
            return respond("Bu isimde zaten bir sunucu var.", 201);
        }
    }

    public function verifyKey()
    {
        hook("server_key_verify", [
            "key" => [
                "key_type" => request('key_type'),
                "ip_address" => request('ip_address'),
                "username" => request('username'),
                "password" => request('password'),
                "port" => request('port'),
            ],
        ]);

        if (request('key_type') == "linux_ssh") {
            return SSHConnector::verify(
                request('ip_address'),
                request('username'),
                request('password'),
                request('port')
            );
        } elseif (request('key_type') == "windows_powershell") {
            return WinRMConnector::verify(
                request('ip_address'),
                request('username'),
                request('password'),
                request('port')
            );
        } elseif (request('key_type') == "linux_certificate") {
            return SSHCertificateConnector::verify(
                request('ip_address'),
                request('username'),
                request('password'),
                request('port')
            );
        } elseif (request('key_type') == "snmp") {
            return SNMPConnector::verifySnmp(
                request('ip_address'),
                request('username'),
                request('SNMPsecurityLevel'),
                request('SNMPauthProtocol'),
                request('SNMPauthPassword'),
                request('SNMPprivacyProtocol'),
                request('SNMPprivacyPassword')
            );
        }
        return respond("Bu anahtara göre bir yapı bulunamadı.", 201);
    }
}
