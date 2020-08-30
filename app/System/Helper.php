<?php

namespace App\System;

use GuzzleHttp\Client;

class Helper {

    private $authKey;
    private $client;
    public function __construct()
    {
        $this->authKey = file_get_contents("/liman/keys/service.key");
        $this->client = new Client([
            "base_uri" => "http://127.0.0.1:3008",
        ]);
    }

    public function userAdd($extension_id)
    {
        try{
            $this->client->get('/userAdd',[
                'query' => [
                    'extension_id' => cleanDash($extension_id),
                    'liman_token' => $this->authKey
                ]
            ]);
        }catch(\Exception $e){
            return false;
        }
        return true;
    }

    public function userRemove($extension_id)
    {
        try{
            $this->client->get('/userRemove',[
                'query' => [
                    'extension_id' => cleanDash($extension_id),
                    'liman_token' => $this->authKey
                ]
            ]);
        }catch(\Exception $e){
            return false;
        }
        return true;
    }

    public function dnsUpdate($server1, $server2, $server3)
    {
        try{
            $this->client->get('/dns',[
                'query' => [
                    'liman_token' => $this->authKey,
                    'server1' => $server1 ?: "",
                    'server2' => $server2 ?: "",
                    'server3' => $server3 ?: ""
                ]
            ]);
        }catch(\Exception $e){
            return false;
        }
        return true;
    }

    public function addCertificate($tmpPath, $targetName)
    {
        try{
            $this->client->get('/certificateAdd',[
                'query' => [
                    'liman_token' => $this->authKey,
                    'tmpPath' => $tmpPath,
                    'targetName' => $targetName,
                ]
            ]);
        }catch(\Exception $e){
            return false;
        }
        return true;
    }

    public function removeCertificate($targetName)
    {
        try{
            $this->client->get('/certificateRemove',[
                'query' => [
                    'liman_token' => $this->authKey,
                    'targetName' => $targetName,
                ]
            ]);
        }catch(\Exception $e){
            return false;
        }
        return true;
    }

    public function fixExtensionPermissions($extension_id, $extension_name)
    {
        try{
            $this->client->get('/fixPermissions',[
                'query' => [
                    'liman_token' => $this->authKey,
                    'extension_id' => cleanDash($extension_id),
                    'extension_name' => strtolower($extension_name)
                ]
            ]);
        }catch(\Exception $e){
            return false;
        }
        return true;
    }

    public function runCommand($user_id, $command,$background = true, $handler = null)
    {
        try{
            $response = $this->client->get('/extensionRun',[
                'query' => [
                    'liman_token' => $this->authKey,
                    'command' => $command,
                    'background' => $background ? "true" : "false",
                    'user_id' => $user_id,
                    'handler' => $handler
                ]
            ]);
        }catch(\Exception $e){
            return __("Liman Sistem Servisine Erişilemiyor!");
        }
        return $response->getBody()->getContents();
    }
}