<?php

namespace App\Http\Controllers\Server;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\Permission;
use App\Models\Server;
use App\Models\ServerKey;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use mervick\aesEverywhere\AES256;

/**
 * Server Add Controller
 *
 * @extends Controller
 */
class AddController extends Controller
{
    /**
     * @var \App\Models\Server
     */
    public $server;

    /**
     * This function creates server in Liman database
     *
     * @return JsonResponse|Response
     * @throws \Exception|GuzzleException
     */
    public function main()
    {
        if (! Permission::can(user()->id, 'liman', 'id', 'add_server')) {
            return respond('Bu işlemi yapmak için yetkiniz yok!', 201);
        }

        // Check if name is already in use.
        if (
            Server::where([
                'user_id' => auth()->id(),
                'name' => request('name'),
            ])->exists()
        ) {
            return respond('Bu sunucu ismiyle bir sunucu zaten var.', 201);
        }

        if (strlen((string) request('name')) > 24) {
            return respond('Lütfen daha kısa bir sunucu adı girin.', 201);
        }

        // Create object with parameters.
        $this->server = new Server();
        $this->server->fill(request()->all());
        $this->server->user_id = auth()->id();
        $this->server->shared_key = request()->shared == 'true' ? 1 : 0;
        if (request('type') == null) {
            $this->server->type = 'none';
        }
        request('key_port')
            ? ($this->server->key_port = request('key_port'))
            : null;

        // Check if Server is online or not.
        if (! $this->server->isAlive()) {
            return respond('Sunucuyla bağlantı kurulamadı.', 406);
        }
        $this->server->save();

        // Send notifications
        // TODO: Add notification for server add.

        // Add Server to request object to use it later.
        request()->request->add(['server' => $this->server]);

        if (request('type')) {
            $encKey = env('APP_KEY') . user()->id . server()->id;
            $data = [
                'clientUsername' => AES256::encrypt(
                    request('username'),
                    $encKey
                ),
                'clientPassword' => AES256::encrypt(
                    request('password'),
                    $encKey
                ),
            ];
            $data['key_port'] = request('key_port');

            ServerKey::updateOrCreate(
                ['server_id' => server()->id, 'user_id' => user()->id],
                ['type' => request('type'), 'data' => json_encode($data)]
            );
        }

        return $this->grantPermissions();
    }

    /**
     * Grant server certificate
     *
     * @return JsonResponse|Response
     * @throws GuzzleException
     * @throws GuzzleException
     */
    private function grantPermissions()
    {
        Permission::grant(user()->id, 'server', 'id', $this->server->id);

        // SSL Control
        if (in_array($this->server->control_port, knownPorts())) {
            $cert = Certificate::where([
                'server_hostname' => $this->server->ip_address,
                'origin' => $this->server->control_port,
            ])->first();
            if (! $cert) {
                [$flag, $message] = retrieveCertificate(
                    request('ip_address'),
                    request('control_port')
                );
                if ($flag) {
                    $flag2 = addCertificate(
                        request('ip_address'),
                        request('control_port'),
                        $message['path']
                    );
                    // TODO: New certificate notification
                }
                if (! $flag || ! $flag2) {
                    $this->server->enabled = false;
                    $this->server->save();
                    // TODO: New certificate notification

                    return respond(
                        __('Bu sunucu ilk defa eklendiğinden dolayı bağlantı sertifikası yönetici onayına sunulmuştur. Bu sürede sunucuya erişemezsiniz.'),
                        202
                    );
                }
            }
        }

        return respond(route('server_one', $this->server->id), 300);
    }
}
