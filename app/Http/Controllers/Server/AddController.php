<?php

namespace App\Http\Controllers\Server;

use App\Models\AdminNotification;
use App\Models\Certificate;
use App\Classes\Connector\SSHConnector;
use App\Classes\Connector\SNMPConnector;
use App\Classes\Connector\SSHCertificateConnector;
use App\Classes\Connector\WinRMConnector;
use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Server;
use Illuminate\Support\Str;
use App\Models\Notification;
use App\Models\UserSettings;

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

        if (
            server()->type == "windows_powershell" ||
            server()->type == "linux_ssh" ||
            server()->type == "linux_certificate"
        ) {
            $encKey = env('APP_KEY') . user()->id . server()->id;
            $encryptedUsername = openssl_encrypt(
                Str::random(16) . base64_encode(request('username')),
                'aes-256-cfb8',
                $encKey,
                0,
                Str::random(16)
            );
            $encryptedPassword = openssl_encrypt(
                Str::random(16) . base64_encode(request('password')),
                'aes-256-cfb8',
                $encKey,
                0,
                Str::random(16)
            );
            UserSettings::create([
                "server_id" => $this->server->id,
                "user_id" => user()->id,
                "name" => "clientUsername",
                "value" => $encryptedUsername,
            ]);
            UserSettings::create([
                "server_id" => $this->server->id,
                "user_id" => user()->id,
                "name" => "clientPassword",
                "value" => $encryptedPassword,
            ]);
        } elseif (server()->type == "snmp") {
            $targetValues = [
                "username",
                "SNMPsecurityLevel",
                "SNMPauthProtocol",
                "SNMPauthPassword",
                "SNMPprivacyProtocol",
                "SNMPprivacyPassword",
            ];
            $encKey = env('APP_KEY') . user()->id . server()->id;
            foreach ($targetValues as $target) {
                $encrypted = openssl_encrypt(
                    Str::random(16) . base64_encode(request($target)),
                    'aes-256-cfb8',
                    $encKey,
                    0,
                    Str::random(16)
                );
                UserSettings::create([
                    "server_id" => $this->server->id,
                    "user_id" => user()->id,
                    "name" => $target,
                    "value" => $encrypted,
                ]);
            }
        }

        // Run required function for specific type.
        $next = null;
        switch ($this->server->type) {
            case "linux":
                $next = $this->linux();
                break;

            case "linux_ssh":
                $next = $this->linux_ssh();
                break;

            case "windows":
                $next = $this->windows();
                break;

            case "windows_powershell":
                $next = $this->windows_powershell();
                break;

            case "linux_certificate":
                $next = $this->linux_certificate();
                break;
            case "snmp":
                $next = $this->snmp();
                break;
            default:
                $next = respond("Sunucu türü bulunamadı.", 404);
                break;
        }
        return $next;
    }

    private function linux_ssh()
    {
        $flag = SSHConnector::create(
            $this->server,
            request('username'),
            request('password'),
            auth()->id(),
            null,
            $this->server->key_port
        );

        if (!$flag) {
            $this->server->delete();
            return respond("SSH Hatası", 400);
        }

        return $this->grantPermissions();
    }

    private function snmp()
    {
        $flag = SNMPConnector::createSnmp(
            $this->server,
            request('username'),
            request('SNMPsecurityLevel'),
            request('SNMPauthProtocol'),
            request('SNMPauthPassword'),
            request('SNMPprivacyProtocol'),
            request('SNMPprivacyPassword'),
            user()->id
        );

        if (!$flag) {
            $this->server->delete();
            return respond("SNMP Hatası", 400);
        }

        return $this->grantPermissions();
    }

    private function linux_certificate()
    {
        $flag = SSHCertificateConnector::create(
            $this->server,
            request('username'),
            request('certificateText'),
            auth()->id(),
            null
        );

        if (!$flag) {
            $this->server->delete();
            return respond("SSH Hatası", 400);
        }

        return $this->grantPermissions();
    }

    private function linux()
    {
        return $this->grantPermissions();
    }

    private function windows()
    {
        return $this->grantPermissions();
    }

    private function windows_powershell()
    {
        $flag = WinRMConnector::create(
            $this->server,
            request('username'),
            request('password'),
            auth()->id(),
            null
        );

        if (!$flag) {
            $this->server->delete();
            return respond("WinRM Hatası", 400);
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
