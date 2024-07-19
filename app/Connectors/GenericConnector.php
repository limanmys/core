<?php

namespace App\Connectors;

use App\Models\Server;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Generic Connector
 * This class connects server and fiber render engine together and uses returned data in Liman
 */
class GenericConnector
{
    /**
     * Construct a new connector instance
     *
     * @param $server
     * @param $user
     */
    public function __construct(public $server = null, public $user = null)
    {
    }

    /**
     * Execute command on remote server
     *
     * @param $command
     * @return string
     * @throws GuzzleException
     */
    public function execute($command): string
    {
        return trim(
            (string) self::request('command', [
                'command' => $command,
            ])
        );
    }

    /**
     * Run extension on remote server
     *
     * @param $url
     * @param $params
     * @param $retry
     * @return never|string
     * @throws GuzzleException
     */
    public function request($url, $params, $retry = 3)
    {
        $client = new Client([
            'verify' => false,
            'connect_timeout' => env('EXTENSION_TIMEOUT', 30),
        ]);

        if ($this->server != null) {
            $params['server_id'] = $this->server->id;
        }

        if ($this->user == null) {
            $params['token'] = auth('api')->setTTL(1000)->tokenById(user()->id);
        } else {
            $params['token'] = auth('api')->setTTL(1000)->tokenById($this->user->id);
        }

        try {
            $response = $client->request(
                'POST',
                env('RENDER_ENGINE_ADDRESS', 'https://127.0.0.1:2806') . "/$url",
                [
                    'form_params' => $params,
                ]
            );

            if ($response->getStatusCode() === 201) {
                $json = json_decode($response->getBody()->getContents());
                if (isset($json->status) && $json->message === "cannot connect to server") {
                    abort(
                        504,
                        __('Cannot connect to server. Please check your connection or credentials.'),
                    );
                }
            }
            return $response->getBody()->getContents();
        } catch (\Exception $exception) {
            $code = 504;
            try {
                if ($exception->getResponse() && $exception->getResponse()->getStatusCode() >= 400) {
                    $code = $exception->getResponse()->getStatusCode();

                    $message = json_decode((string) $exception->getResponse()->getBody()->getContents())->message;
                    if ($message == '') {
                        $message = $exception->getMessage();
                    }
                } else {
                    $message = $exception->getMessage();
                }
            } catch (\Throwable) {
                $message = $exception->getMessage();
            }

            if (env('APP_DEBUG', false)) {
                return abort(
                    504,
                    __('Liman render service is not working or crashed. ') . $message,
                );
            } else {
                return abort(
                    504,
                    __('Liman render service is not working or crashed. '),
                );
            }
        }
    }

    /**
     * @param Server $server
     * @param $username
     * @param $password
     * @param $user_id
     * @param $key
     * @param $port
     * @return void
     */
    public function create(
        \App\Models\Server $server,
                           $username,
                           $password,
                           $user_id,
                           $key,
                           $port = null
    )
    {
    }

    /**
     * Send file to remote server
     *
     * @param $localPath
     * @param $remotePath
     * @param $permissions
     * @return string
     * @throws GuzzleException
     */
    public function sendFile($localPath, $remotePath, $permissions = 0644): string
    {
        return trim(
            (string) self::request('putFile', [
                'local_path' => $localPath,
                'remote_path' => $remotePath,
            ])
        );
    }

    /**
     * Receive file from remote server
     *
     * @param $localPath
     * @param $remotePath
     * @return string
     * @throws GuzzleException
     */
    public function receiveFile($localPath, $remotePath): string
    {
        return trim(
            (string) self::request('getFile', [
                'local_path' => $localPath,
                'remote_path' => $remotePath,
            ])
        );
    }

    /**
     * Verify if extension data is eligible to run
     *
     * @param $ip_address
     * @param $username
     * @param $password
     * @param $port
     * @param $type
     * @return string
     * @throws GuzzleException
     */
    public function verify($ip_address, $username, $password, $port, $type): string
    {
        return trim(
            (string) self::request('verify', [
                'ip_address' => $ip_address,
                'username' => $username,
                'password' => $password,
                'port' => $port,
                'key_type' => $type,
            ])
        );
    }
}
