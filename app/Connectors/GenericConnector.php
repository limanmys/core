<?php

namespace App\Connectors;

use App\Models\Token;
use GuzzleHttp\Client;

class GenericConnector
{
    public $server;

    public $user;

    public function __construct(\App\Models\Server $server = null, $user = null)
    {
        $this->server = $server;
        $this->user = $user;
    }

    public function execute($command)
    {
        return trim(
            self::request('command', [
                'command' => $command,
            ])
        );
    }

    public function sendFile($localPath, $remotePath, $permissions = 0644)
    {
        return trim(
            self::request('putFile', [
                'local_path' => $localPath,
                'remote_path' => $remotePath,
            ])
        );
    }

    public function receiveFile($localPath, $remotePath)
    {
        return trim(
            self::request('getFile', [
                'local_path' => $localPath,
                'remote_path' => $remotePath,
            ])
        );
    }

    public function verify($ip_address, $username, $password, $port, $type)
    {
        return trim(
            self::request('verify', [
                'ip_address' => $ip_address,
                'username' => $username,
                'password' => $password,
                'port' => $port,
                'key_type' => $type,
            ])
        );
    }

    public function create(
        \App\Models\Server $server,
        $username,
        $password,
        $user_id,
        $key,
        $port = null
    ) {
    }

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
            $params['token'] = Token::create(user()->id);
        } else {
            $params['token'] = Token::create($this->user->id);
        }

        try {
            $response = $client->request(
                'POST',
                env('RENDER_ENGINE_ADDRESS', 'https://127.0.0.1:2806')."/$url",
                [
                    'form_params' => $params,
                ]
            );

            return $response->getBody()->getContents();
        } catch (\Exception $exception) {
            $code = 504;
            try {
                if ($exception->getResponse() && $exception->getResponse()->getStatusCode() >= 400) {
                    $code = $exception->getResponse()->getStatusCode();

                    $message = json_decode($exception->getResponse()->getBody()->getContents())->message;
                    if ($message == '') {
                        $message = $exception->getMessage();
                    }
                } else {
                    $message = $exception->getMessage();
                }
            } catch (\Throwable $e) {
                $message = $exception->getMessage();
            }

            if (env('APP_DEBUG', false)) {
                return abort(
                    504,
                    __('Liman render service is not working or crashed. ').$message,
                );
            } else {
                return abort(
                    504,
                    __('Liman render service is not working or crashed. '),
                );
            }
        }
    }
}
