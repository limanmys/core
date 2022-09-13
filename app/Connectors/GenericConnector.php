<?php

namespace App\Connectors;

use GuzzleHttp\Client;
use App\Models\Token;

class GenericConnector
{
    public $server, $user;

    public function __construct(\App\Models\Server $server = null, $user = null)
    {
        $this->server = $server;
        $this->user = $user;
    }

    public function execute($command)
    {
        return trim(
            self::request('command', [
                "command" => $command,
            ])
        );
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

    public function receiveFile($localPath, $remotePath)
    {
        return trim(
            self::request('getFile', [
                "localPath" => $localPath,
                "remotePath" => $remotePath,
            ])
        );
    }

    public function runScript($script, $parameters, $runAsRoot = false)
    {
        return trim(
            self::request('getFile', [
                "script" => $script,
                "parameters" => $parameters,
                "runAsRoot" => $runAsRoot,
            ])
        );
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

    public function verify($ip_address, $username, $password, $port, $type)
    {
        return trim(
            self::request('verify', [
                "ip_address" => $ip_address,
                "username" => $username,
                "password" => $password,
                "port" => $port,
                "key_type" => $type,
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
            'connect_timeout' => env("EXTENSION_TIMEOUT", 30)
        ]);

        if ($this->server != null) {
            $params["server_id"] = $this->server->id;
        }

        if ($this->user == null) {
            $params["token"] = Token::create(user()->id);
        } else {
            $params["token"] = Token::create($this->user->id);
        }

        try {
            $response = $client->request(
                'POST',
                env("RENDER_ENGINE_ADDRESS","https://127.0.0.1:2806"). "/$url",
                [
                    "form_params" => $params,
                ]
            );
            return $response->getBody()->getContents();
        } catch (\Exception $exception) {
            $code = 500;
			try {
				if ($exception->getResponse() && $exception->getResponse()->getStatusCode() >= 400) {
					$code = $exception->getResponse()->getStatusCode();
				
					$message = json_decode($exception->getResponse()->getBody()->getContents())->message;
					if ($message == "") {
						$message = $exception->getMessage();
					}
				} else {
					$message = $exception->getMessage();
				}
			} catch (\Throwable $e) {
				$message = $exception->getMessage();
			}

            abort($code, $message);
            return null;
        }
    }
}
