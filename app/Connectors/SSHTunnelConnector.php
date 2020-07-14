<?php

namespace App\Connectors;

use App\Models\TunnelToken;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;

/**
 * Class SSHTunnelConnector
 * @package App\Connectors
 */
class SSHTunnelConnector
{
    public static function new($remote_host, $remote_port, $username, $password)
    {
        if (TunnelToken::get($remote_host, $remote_port)->exists()) {
            $tunnel = TunnelToken::get($remote_host, $remote_port)->first();
            if (checkPort("127.0.0.1", $tunnel->local_port)) {
                return $tunnel->local_port;
            }
        }
        $req = self::request("new", [
            "connection_type" => "ssh_tunnel",
            "username" => $username,
            "password" => $password,
            "hostname" => $remote_host,
            "remote_port" => $remote_port,
        ]);
        $token_parse = explode(':', $req->token);
        TunnelToken::set(
            $token_parse[0],
            $token_parse[1],
            $remote_host,
            $remote_port
        );
        return $token_parse[1];
    }

    public static function stop($remote_host, $remote_port)
    {
        if (TunnelToken::get($remote_host, $remote_port)->exists()) {
            $tunnel = TunnelToken::get($remote_host, $remote_port)->first();
            TunnelToken::remove($tunnel->token);
            self::request("stop", [
                "token" => $tunnel->token . ":" . $tunnel->local_port,
            ]);
            return true;
        }
        return null;
    }

    public static function request($url, $params, $retry = 3)
    {
        // Create Guzzle Object.
        $client = new Client();
        // Make Request.
        try {
            $res = $client->request('POST', 'http://127.0.0.1:5000/' . $url, [
                "form_params" => $params,
            ]);
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
                return self::request($url, $params, $retry - 1);
            } else {
                // If nothing works, abort.
                abort(402, "Tünel işlemleri sırasında bir hata oluştu.");
            }
        }
        // Simply parse and return output.
        $json = json_decode((string) $res->getBody());
        return $json;
    }
}
