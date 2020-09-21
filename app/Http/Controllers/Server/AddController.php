<?php

namespace App\Http\Controllers\Server;

use App\Models\AdminNotification;
use App\Models\Certificate;
use App\Models\ServerKey;
use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Server;
use Illuminate\Support\Str;
use App\Models\Notification;
use App\Models\UserSettings;
use mervick\aesEverywhere\AES256;

class AddController extends Controller
{
    /**
     * @var \App\Models\Server
     */
    public $server;

    public function main()
    {
        if (!Permission::can(user()->id, 'liman', 'id', 'add_server')) {
            return respond("Bu işlemi yapmak için yetkiniz yok!", 201);
        }

        hook('server_add_attempt', [
            "request" => request()->all(),
        ]);

        // Check if name is already in use.
        if (
            Server::where([
                'user_id' => auth()->id(),
                "name" => request('name'),
            ])->exists()
        ) {
            return respond("Bu sunucu ismiyle bir sunucu zaten var.", 201);
        }

        if (strlen(request('name')) > 24) {
            return respond("Lütfen daha kısa bir sunucu adı girin.", 201);
        }

        // Create object with parameters.
        $this->server = new Server();
        $this->server->fill(request()->all());
        $this->server->user_id = auth()->id();
        request('key_port')
            ? ($this->server->key_port = request('key_port'))
            : null;
        // Check if Server is online or not.
        if (!$this->server->isAlive()) {
            return respond("Sunucuyla bağlantı kurulamadı.", 406);
        }

        $this->server->save();
        Notification::new(
            __("Yeni sunucu eklendi."),
            "notify",
            __(":server (:ip) isimli yeni bir sunucu eklendi.", [
                "server" => $this->server->name,
                "ip" => $this->server->ip_address,
            ])
        );
        // Add Server to request object to use it later.
        request()->request->add(["server" => $this->server]);

        if (request('type')) {
            $encKey = env('APP_KEY') . user()->id . server()->id;
            $data = [
                "clientUsername" => AES256::encrypt(
                    request('username'),
                    $encKey
                ),
                "clientPassword" => AES256::encrypt(
                    request('password'),
                    $encKey
                ),
            ];
            $data["key_port"] = request('key_port');

            ServerKey::updateOrCreate(
                ["server_id" => server()->id, "user_id" => user()->id],
                ["type" => request('type'), "data" => json_encode($data)]
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
            if (!$cert) {
                list($flag, $message) = retrieveCertificate(
                    request('ip_address'),
                    request('control_port')
                );
                if ($flag) {
                    $flag2 = addCertificate(
                        request('ip_address'),
                        request('control_port'),
                        $message["path"]
                    );
                    AdminNotification::create([
                        "title" => "Yeni Sertifika Eklendi",
                        "type" => "new_cert",
                        "message" =>
                            "Sisteme yeni sunucu eklendi ve yeni bir sertifika eklendi.<br><br><a href='" .
                            route('settings') .
                            "#certificates'>Detaylar</a>",
                        "level" => 3,
                    ]);
                }
                if (!$flag || !$flag2) {
                    $this->server->enabled = false;
                    $this->server->save();
                    AdminNotification::create([
                        "title" => "Yeni Sertifika Onayı",
                        "type" => "cert_request",
                        "message" =>
                            $this->server->ip_address .
                            ":" .
                            $this->server->control_port .
                            ":" .
                            $this->server->id,
                        "level" => 3,
                    ]);
                    return respond(
                        "Bu sunucu ilk defa eklendiğinden dolayı bağlantı sertifikası yönetici onayına sunulmuştur. Bu sürede sunucuya erişemezsiniz.",
                        202
                    );
                }
            }
        }
        hook("server_add_successful", ["server" => $this->server]);

        return respond(route('server_one', $this->server->id), 300);
    }
}
