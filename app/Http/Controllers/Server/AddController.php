<?php

namespace App\Http\Controllers\Server;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use App\Models\Certificate;
use App\Models\Notification;
use App\Models\Permission;
use App\Models\Server;
use App\Models\ServerKey;
use mervick\aesEverywhere\AES256;

class AddController extends Controller
{
    /**
     * @var \App\Models\Server
     */
    public $server;

    public function main()
    {
        if (! Permission::can(user()->id, 'liman', 'id', 'add_server')) {
            return respond('Bu işlemi yapmak için yetkiniz yok!', 201);
        }

        hook('server_add_attempt', [
            'request' => request()->all(),
        ]);

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
        Notification::new(
            'Yeni sunucu eklendi.',
            'notify',
            json_encode([
                'tr' => __(':server (:ip) isimli yeni bir sunucu eklendi.', [
                    'server' => $this->server->name,
                    'ip' => $this->server->ip_address,
                ], 'tr'),
                'en' => __(':server (:ip) isimli yeni bir sunucu eklendi.', [
                    'server' => $this->server->name,
                    'ip' => $this->server->ip_address,
                ], 'en'),
            ])
        );
        // Add Server to request object to use it later.
        request()->request->add(['server' => $this->server]);

        if (request('type')) {
            $encKey = env('APP_KEY').user()->id.server()->id;
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
                    AdminNotification::create([
                        'title' => json_encode([
                            'tr' => __('Yeni Sertifika Eklendi', [], 'tr'),
                            'en' => __('Yeni Sertifika Eklendi', [], 'en'),
                        ]),
                        'type' => 'new_cert',
                        'message' => json_encode([
                            'tr' => __('Sisteme yeni sunucu eklendi ve yeni bir sertifika eklendi.', [], 'tr'),
                            'en' => __('Sisteme yeni sunucu eklendi ve yeni bir sertifika eklendi.', [], 'en'),
                        ]),
                        'level' => 3,
                    ]);
                }
                if (! $flag || ! $flag2) {
                    $this->server->enabled = false;
                    $this->server->save();
                    AdminNotification::create([
                        'title' => json_encode([
                            'tr' => __('Yeni Sertifika Onayı', [], 'tr'),
                            'en' => __('Yeni Sertifika Onayı', [], 'en'),
                        ]),
                        'type' => 'cert_request',
                        'message' => $this->server->ip_address.
                            ':'.
                            $this->server->control_port.
                            ':'.
                            $this->server->id,
                        'level' => 3,
                    ]);

                    return respond(
                        __('Bu sunucu ilk defa eklendiğinden dolayı bağlantı sertifikası yönetici onayına sunulmuştur. Bu sürede sunucuya erişemezsiniz.'),
                        202
                    );
                }
            }
        }
        hook('server_add_successful', ['server' => $this->server]);

        return respond(route('server_one', $this->server->id), 300);
    }
}
