<?php

namespace App\Http\Controllers\Server;

use App\Connectors\GenericConnector;
use App\Http\Controllers\Controller;
use App\Models\Server;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * Server Controller
 *
 * @extends Controller
 */
class MainController extends Controller
{
    /**
     * Retrieve all servers
     *
     * @return Application|Factory|View|JsonResponse
     */
    public function all()
    {
        if (request()->wantsJson()) {
            return response()->json(servers());
        } else {
            return view('server.index');
        }
    }

    /**
     * Retrieve a server
     *
     * @return JsonResponse
     */
    public function oneData()
    {
        return response()->json(server());
    }

    /**
     * Check if server is active
     *
     * @return JsonResponse|Response
     */
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

    /**
     * Check if server name is valid
     *
     * @return JsonResponse|Response
     */
    public function verifyName()
    {
        if (strlen((string) request('server_name')) > 24) {
            return respond('Lütfen daha kısa bir sunucu adı girin.', 201);
        }
        if (! Server::where('name', request('server_name'))->exists()) {
            return respond('İsim Onaylandı.', 200);
        } else {
            return respond('Bu isimde zaten bir sunucu var.', 201);
        }
    }

    /**
     * Check if server key is valid
     *
     * @return JsonResponse|Response
     * @throws GuzzleException
     */
    public function verifyKey()
    {
        $connector = new GenericConnector();
        $output = $connector->verify(
            request('ip_address'),
            request('username'),
            request('password'),
            request('port'),
            request('key_type')
        );

        if ($output == 'ok') {
            return respond('Anahtarınız doğrulandı!');
        } else {
            return respond('Anahtarınız doğrulanamadı!', 201);
        }
    }
}
