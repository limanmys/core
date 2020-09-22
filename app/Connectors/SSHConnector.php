<?php

namespace App\Connectors;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use App\Models\UserSettings;
use App\Models\Token;
use Illuminate\Support\Str;
use App\Models\ConnectorToken;

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
     * @param \App\Models\Server $server
     * @param null $user_id
     */
    public function __construct(\App\Models\Server $server, $user_id)
    {
        return true;
    }

    public function execute($command, $flag = true)
    {
        return trim(
            self::request('runCommand', [
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
        return trim(
            self::request('putFile', [
                "localPath" => $localPath,
                "remotePath" => $remotePath,
            ])
        );
    }

    public static function verify($ip_address, $username, $password, $port)
    {
        $token = self::init($username, $password, $ip_address, $port, false);
        if ($token) {
            return respond("Kullanıcı adı ve şifre doğrulandı.", 200);
        }
        return respond("Bu Kullanıcı adı ve şifre ile bağlanılamadı.", 201);
    }

    public function receiveFile($localPath, $remotePath)
    {
        return trim(
            self::request('getFile', [
                "localPath" => $localPath,
                "remotePath" => $remotePath,
            ])
        );
    }

    /**
     * @param \App\Models\Server $server
     * @param $username
     * @param $password
     * @param $user_id
     * @param $key
     * @return bool
     */
    public static function create(
        \App\Models\Server $server,
        $username,
        $password,
        $user_id,
        $key,
        $port = null
    ) {
        $token = self::init(
            $username,
            $password,
            $server->ip_address,
            $port ? $port : 22
        );
        if ($token) {
            return true;
        } else {
            return false;
        }
    }

    public static function request($url, $params, $retry = 3)
    {
        $client = new Client(['verify' => false]);
        $params["server_id"] = server()->id;
        $params["token"] = Token::create(user()->id);
        try {
            $response = $client->request(
                'POST',
                "https://127.0.0.1:5454/$url",
                [
                    "form_params" => $params,
                ]
            );
            return $response->getBody()->getContents();
        } catch (GuzzleException $exception) {
            return $exception->getMessage();
        }
        $json = json_decode((string) $res->getBody());
        return $json->output;
    }

    public static function init(
        $username,
        $password,
        $hostname,
        $port,
        $putSession = true
    ) {
        $client = new Client();
        try {
            $res = $client->request('POST', 'http://127.0.0.1:5000/new', [
                'form_params' => [
                    "username" => $username,
                    "password" => $password,
                    "hostname" => $hostname,
                    "port" => $port,
                    "connection_type" => "ssh",
                ],
                'timeout' => 5,
            ]);
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
