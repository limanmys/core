<?php

namespace App\Connectors;

use App\Models\Server;
use App\Models\UserSettings;
use GuzzleHttp\Client;
use Illuminate\Support\Str;
use App\Models\ConnectorToken;
use mervick\aesEverywhere\AES256;

class WinRMConnector implements Connector
{
    public function __construct(Server $server, $user_id)
    {
        if (!ConnectorToken::get($server->id)->exists()) {
            list($username, $password) = self::retrieveCredentials();
            self::init($username, $password, $server->ip_address);
        }
        return true;
    }

    public static function retrieveCredentials()
    {
        if (server()->key() == null) {
            abort(
                504,
                "Bu sunucu için WinRM anahtarınız yok. Kasa üzerinden bir anahtar ekleyebilirsiniz."
            );
        }
        $data = json_decode(server()->key()->data, true);

        return [
            lDecrypt($data["clientUsername"]),
            lDecrypt($data["clientPassword"]),
            array_key_exists("key_port", $data)
                ? intval($data["key_port"])
                : 5986,
        ];
    }

    public function execute($command)
    {
        // Prepare Powershell Command
        $command =
            "powershell.exe -encodedCommand " .
            base64_encode(
                mb_convert_encoding(
                    "\$ProgressPreference = \"SilentlyContinue\"; " . $command,
                    "UTF-16LE",
                    "UTF-8"
                )
            );
        return trim(
            self::request('run', [
                "token" => "cn_" . server()->id,
                "command" => $command,
            ])
        );
    }

    public static function request($url, $params, $retry = 3)
    {
        if (!ConnectorToken::get(server()->id)->exists()) {
            list($username, $password) = self::retrieveCredentials();
            self::init($username, $password, server()->id);
        }
        // Create Guzzle Object.
        $client = new Client();
        // Make Request.

        try {
            $params["token"] = ConnectorToken::get(
                server()->id
            )->first()->token;
            $res = $client->request('POST', 'http://127.0.0.1:5000/' . $url, [
                "form_params" => $params,
                'timeout' => 5,
            ]);
        } catch (\Exception $e) {
            // In case of error, handle error.
            $json = json_decode(
                (string) $e
                    ->getResponse()
                    ->getBody()
                    ->getContents()
            );

            // If it's first time, retry after recreating ticket.
            if ($retry) {
                list($username, $password) = self::retrieveCredentials();
                self::init($username, $password, server()->ip_address);
                return self::request($url, $params, $retry - 1);
            } else {
                // If nothing works, abort.
                abort(402, "Anahtarınız ile sunucuya giriş yapılamadı.");
            }
        }

        // Simply parse and return output.
        $json = json_decode((string) $res->getBody());
        return $json->output;
    }

    public function sendFile($localPath, $remotePath, $permissions = 0644)
    {
        return self::request('send', [
            "token" => ConnectorToken::get(server()->id)->first()->token,
            "local_path" => $localPath,
            "remote_path" => $remotePath,
        ]);
    }

    public function receiveFile($localPath, $remotePath)
    {
        return self::request('get', [
            "token" => ConnectorToken::get(server()->id)->first()->token,
            "local_path" => $localPath,
            "remote_path" => $remotePath,
        ]);
    }

    public function runScript($script, $parameters, $runAsRoot = false)
    {
        // Find Remote Path
        $remotePath = "\\Windows\\Temp\\" . Str::random() . ".ps1";

        $flag = $this->sendFile($script, $remotePath);

        // Find Out Letter. DISABLED FOR NOW
        // $letter = $this->execute("\$pwd.drive.name");

        // $letter = substr($letter,0, -2);

        $query = "C:\\" . $remotePath . " " . $parameters;

        $output = $this->execute($query);

        return $output;
    }

    public static function verify($ip_address, $username, $password, $port)
    {
        $token = self::init($username, $password, $ip_address, false);
        if ($token) {
            return respond("Kullanıcı adı ve şifre doğrulandı.", 200);
        }
        return respond("Bu Kullanıcı adı ve şifre ile bağlanılamadı.", 201);
    }

    public static function init(
        $username,
        $password,
        $hostname,
        $putSession = true
    ) {
        $client = new Client();
        try {
            $res = $client->request('POST', 'http://127.0.0.1:5000/new', [
                'form_params' => [
                    "username" => $username,
                    "password" => $password,
                    "hostname" => $hostname,
                    "connection_type" => "winrm",
                ],
                'timeout' => 5,
            ]);
        } catch (\Exception $e) {
            return null;
        }

        $json = json_decode((string) $res->getBody());
        if ($putSession) {
            if (auth() && auth()->user()) {
                ConnectorToken::set($json->token, server()->id);
            }
        }

        return $json->token;
    }

    public static function create(
        Server $server,
        $username,
        $password,
        $user_id,
        $key,
        $port = null
    ) {
        $token = self::init($username, $password, $server->ip_address);
        if ($token) {
            return true;
        } else {
            return false;
        }
    }
}
