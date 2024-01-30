<?php

namespace App\Connectors;

use App\Models\Server;
use App\Models\Token;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class GenericConnector
{
    protected ?Server $server;
    protected ?User $user;

    public function __construct(?Server $server = null, ?User $user = null)
    {
    }

    public function execute(string $command): string
    {
        return trim(
            (string) $this->request('command', [
                'command' => $command,
            ])
        );
    }

    public function request(string $url, array $params, int $retry = 3): string
    {
        $client = new Client([
            'verify' => false,
            'connect_timeout' => config('app.extension_timeout', 30),
        ]);

        if ($this->server !== null) {
            $params['server_id'] = $this->server->id;
        }

        if ($this->user === null) {
            $params['token'] = Token::create(auth()->id());
        } else {
            $params['token'] = Token::create($this->user->id);
        }

        try {
            $response = $client->request(
                'POST',
                config('app.render_engine_address', 'https://127.0.0.1:2806') . "/$url",
                [
                    'form_params' => $params,
                ]
            );

            return $response->getBody()->getContents();
        } catch (\Exception $exception) {
            return $this->handleException($exception);
        }
    }

    public function sendFile(string $localPath, string $remotePath, int $permissions = 0644): string
    {
        return trim(
            (string) $this->request('putFile', [
                'local_path' => $localPath,
                'remote_path' => $remotePath,
            ])
        );
    }

    public function receiveFile(string $localPath, string $remotePath): string
    {
        return trim(
            (string) $this->request('getFile', [
                'local_path' => $localPath,
                'remote_path' => $remotePath,
            ])
        );
    }

    public function verify(string $ipAddress, string $username, string $password, int $port, string $type): string
    {
        return trim(
            (string) $this->request('verify', [
                'ip_address' => $ipAddress,
                'username' => $username,
                'password' => $password,
                'port' => $port,
                'key_type' => $type,
            ])
        );
    }

    private function handleException(\Exception $exception): string
    {
        $code = 504;
        $message = $exception->getMessage();

        try {
            if ($exception->getResponse() && $exception->getResponse()->getStatusCode() >= 400) {
                $code = $exception->getResponse()->getStatusCode();

                $message = json_decode((string) $exception->getResponse()->getBody()->getContents())->message;
                if ($message == '') {
                    $message = $exception->getMessage();
                }
            }
        } catch (\Throwable) {
            // Handle the error situation
        }

        $this->notifyError($message);

        if (config('app.debug', false)) {
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

    private function notifyError(string $errorMessage)
    {
        //Burayı monitörler için kullanılabilir 
    }
}
