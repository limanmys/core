<?php

namespace App\Http\Controllers\Server;

use App\Connectors\GenericConnector;
use App\Connectors\SNMPConnector;
use App\Http\Controllers\Controller;
use App\Models\Server;

class MainController extends Controller
{
    public function all()
    {
        if (request()->wantsJson()) {
            return response()->json(servers());
        } else {
            return view('server.index');
        }
    }

    public function oneData()
    {
        return response()->json(server());
    }

    public function checkAccess()
    {
        if (request('port') == -1) {
            return respond('Sunucuya başarıyla erişim sağlandı.', 200);
        }
        $status = @fsockopen(
            request('hostname'),
            request('port'),
            $errno,
            $errstr,
            intval(config('liman.server_connection_timeout')) / 1000
        );
        if (is_resource($status)) {
            return respond('Sunucuya başarıyla erişim sağlandı.', 200);
        } else {
            return respond('Sunucuya erişim sağlanamadı.', 201);
        }
    }

    public function verifyName()
    {
        if (strlen(request('server_name')) > 24) {
            return respond('Lütfen daha kısa bir sunucu adı girin.', 201);
        }
        if (! Server::where('name', request('server_name'))->exists()) {
            return respond('İsim Onaylandı.', 200);
        } else {
            return respond('Bu isimde zaten bir sunucu var.', 201);
        }
    }

    public function verifyKey()
    {
        hook('server_key_verify', [
            'key' => [
                'key_type' => request('key_type'),
                'ip_address' => request('ip_address'),
                'username' => request('username'),
                'password' => request('password'),
                'port' => request('port'),
            ],
        ]);

        if (request('key_type') == 'snmp') {
            $output = SNMPConnector::verifySnmp(
                request('ip_address'),
                request('username'),
                request('password')
            );
        } else {
            $connector = new GenericConnector();
            $output = $connector->verify(
                request('ip_address'),
                request('username'),
                request('password'),
                request('port'),
                request('key_type')
            );
        }

        if ($output == 'ok') {
            return respond('Anahtarınız doğrulandı!');
        } else {
            return respond('Anahtarınız doğrulanamadı!', 201);
        }
    }
}
