<?php

namespace App\System;

use App\Models\SystemSettings;
use GuzzleHttp\Client;

class Helper
{
    private $authKey;

    private $client;

    public function __construct()
    {
        $this->authKey = file_get_contents('/liman/keys/service.key');
        $this->client = new Client([
            'base_uri' => 'http://127.0.0.1:3008',
        ]);
    }

    public function userAdd($extension_id)
    {
        try {
            $this->client->get('/userAdd', [
                'query' => [
                    'extension_id' => cleanDash($extension_id),
                    'liman_token' => $this->authKey,
                ],
            ]);
        } catch(\Exception $e) {
            return false;
        }

        return true;
    }

    public function userRemove($extension_id)
    {
        try {
            $this->client->get('/userRemove', [
                'query' => [
                    'extension_id' => cleanDash($extension_id),
                    'liman_token' => $this->authKey,
                ],
            ]);
        } catch(\Exception $e) {
            return false;
        }

        return true;
    }

    public function dnsUpdate($server1, $server2, $server3)
    {
        try {
            $this->client->get('/dns', [
                'query' => [
                    'liman_token' => $this->authKey,
                    'server1' => $server1 ?: '',
                    'server2' => $server2 ?: '',
                    'server3' => $server3 ?: '',
                ],
            ]);
        } catch(\Exception $e) {
            return false;
        }

        SystemSettings::updateOrCreate(
            ['key' => 'SYSTEM_DNS'],
            ['data' => json_encode([
                $server1, $server2, $server3,
            ])]
        );

        return true;
    }

    public function addCertificate($tmpPath, $targetName)
    {
        $contents = $tmpPath;
        if (is_file($tmpPath)) {
            $contents = file_get_contents($tmpPath);
        } else {
            $tmpPath = '/tmp/'.str_random(16);
            file_put_contents($tmpPath, $contents);
        }
        $arr = [
            'certificate' => $contents,
            'targetName' => $targetName,
        ];

        $current = SystemSettings::where('key', 'SYSTEM_CERTIFICATES')->first();

        if ($current) {
            $foo = json_decode($current->data, true);
            $flag = true;
            for ($i = 0; $i < count($foo); $i++) {
                if ($foo[$i]['targetName'] == $targetName) {
                    $foo[$i]['certificate'] = $arr['certificate'];
                    $flag = false;
                    break;
                }
            }

            if ($flag) {
                array_push($foo, $arr);
            }

            $current->update([
                'data' => json_encode($foo),
            ]);
        } else {
            SystemSettings::create([
                'key' => 'SYSTEM_CERTIFICATES',
                'data' => json_encode([$arr]),
            ]);
        }

        try {
            $this->client->get('/certificateAdd', [
                'query' => [
                    'liman_token' => $this->authKey,
                    'tmpPath' => $tmpPath,
                    'targetName' => $targetName,
                ],
            ]);
        } catch(\Exception $e) {
            return false;
        }

        return true;
    }

    public function removeCertificate($targetName)
    {
        $arr = [
            'targetName' => $targetName,
        ];

        $current = SystemSettings::where('key', 'SYSTEM_CERTIFICATES')->first();

        if ($current) {
            $foo = json_decode($current->data, true);
            for ($i = 0; $i < count($foo); $i++) {
                if ($foo[$i]['targetName'] == $targetName) {
                    unset($foo[$i]);
                    $foo = array_values($foo);
                    break;
                }
            }
            $current->update([
                'data' => $foo,
            ]);
        }

        try {
            $this->client->get('/certificateRemove', [
                'query' => [
                    'liman_token' => $this->authKey,
                    'targetName' => $targetName,
                ],
            ]);
        } catch(\Exception $e) {
            return false;
        }

        return true;
    }

    public function fixExtensionPermissions($extension_id, $extension_name)
    {
        try {
            $this->client->get('/fixPermissions', [
                'query' => [
                    'liman_token' => $this->authKey,
                    'extension_id' => $extension_id,
                    'extension_name' => strtolower($extension_name),
                ],
            ]);
        } catch(\Exception $e) {
            return false;
        }

        return true;
    }

    public function installPackages($packages)
    {
        try {
            $this->client->get('/installPackages', [
                'query' => [
                    'liman_token' => $this->authKey,
                    'packages' => $packages,
                ],
            ]);
        } catch(\Exception $e) {
            return false;
        }

        return true;
    }

    public function runCommand($command)
    {
        try {
            $response = $this->client->get('/extensionRun', [
                'query' => [
                    'liman_token' => $this->authKey,
                    'command' => $command,
                ],
            ]);
        } catch(\Exception $e) {
            return __('Liman Sistem Servisine Erişilemiyor!');
        }

        return $response->getBody()->getContents();
    }
}
