<?php

namespace App\Classes\Connector;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use App\UserSettings;
use Illuminate\Support\Str;
use App\ConnectorToken;

/**
 * Class SSHConnector
 * @package App\Classes
 */
class SSHConnector implements Connector
{
    /**
     * @var mixed
     */
    protected $connection;
    protected $server;
    protected $ssh;
    protected $key;
    protected $user_id;
    protected $username;

    /**
     * SSHConnector constructor.
     * @param \App\Server $server
     * @param null $user_id
     */
    public function __construct(\App\Server $server, $user_id)
    {
        if (!ConnectorToken::get($server->id)->exists()) {
            list($username, $password) = self::retrieveCredentials();
            self::init($username, $password, $server->ip_address);
        }

        return true;
    }

    public function execute($command, $flag = true)
    {
        return trim(
            self::request('run', [
                "token" => "cn_" . server()->id,
                "command" => $command,
            ])
        );
    }

    /**
     * @param $script
     * @param $parameters
     * @param null $extra
     * @return string
     */
    public function runScript($script, $parameters, $runAsRoot = false)
    {
        $remotePath = "/tmp/" . Str::random();

        $this->sendFile($script, $remotePath);
        $output = $this->execute("[ -f '$remotePath' ] && echo 1 || echo 0");
        if ($output != "1") {
            abort(504, "Betik gönderilemedi");
        }
        $this->execute("chmod +x " . $remotePath);

        // Run Part Of The Script
        $query = $runAsRoot ? sudo() : '';
        $query = $query . $remotePath . " " . $parameters . " 2>&1";
        $output = $this->execute($query);

        return $output;
    }

    public function sendFile($localPath, $remotePath, $permissions = 0644)
    {
        $output = self::request('send', [
            "token" => ConnectorToken::get(server()->id)->first()->token,
            "local_path" => $localPath,
            "remote_path" => $remotePath,
        ]);
        $check = $this->execute("[ -f '$remotePath' ] && echo 1 || echo 0");
        if ($check != "1") {
            abort(504, "Dosya gönderilemedi");
        }
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

    public function receiveFile($localPath, $remotePath)
    {
        return self::request('get', [
            "token" => ConnectorToken::get(server()->id)->first()->token,
            "local_path" => $localPath,
            "remote_path" => $remotePath,
        ]);
    }

    /**
     * @param \App\Server $server
     * @param $username
     * @param $password
     * @param $user_id
     * @param $key
     * @return bool
     */
    public static function create(
        \App\Server $server,
        $username,
        $password,
        $user_id,
        $key
    ) {
        $token = self::init($username, $password, $server->ip_address);
        if ($token) {
            return true;
        } else {
            return false;
        }
    }

    public static function retrieveCredentials()
    {
        $username = UserSettings::where([
            'user_id' => user()->id,
            'server_id' => server()->id,
            'name' => 'clientUsername',
        ])->first();
        $password = UserSettings::where([
            'user_id' => user()->id,
            'server_id' => server()->id,
            'name' => 'clientPassword',
        ])->first();

        if (!$username || !$password) {
            abort(
                504,
                "Bu sunucu için SSH anahtarınız yok. Kasa üzerinden bir anahtar ekleyebilirsiniz."
            );
        }

        return [lDecrypt($username["value"]), lDecrypt($password["value"])];
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
            $res = $client->request(
                'POST',
                'http://127.0.0.1:5000/' . $url,
                ["form_params" => $params]
            );
        } catch (BadResponseException $e) {
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
                abort(402, "Anahtarınız ile sunucuya giriş yapılamadı");
            }
        }
        // Simply parse and return output.
        $json = json_decode((string) $res->getBody());
        return $json->output;
    }

    public static function init(
        $username,
        $password,
        $hostname,
        $putSession = true
    ) {
        $client = new Client();
        try {
            $res = $client->request(
                'POST',
                'http://127.0.0.1:5000/new',
                [
                    'form_params' => [
                        "username" => $username,
                        "password" => $password,
                        "hostname" => $hostname,
                        "connection_type" => "ssh",
                    ],
                    'timeout' => 5,
                ]
            );
        } catch (\Exception $e) {
            return null;
        }

        $json = json_decode((string) $res->getBody());
        //Escape For . character in session.
        if ($putSession) {
            if (auth() && auth()->user()) {
                ConnectorToken::set($json->token, server()->id);
            }
        }

        return $json->token;
    }
}
