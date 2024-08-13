<?php

namespace App\System;

use App\Models\SystemSettings;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Translation\Translator;

/**
 * System Helper
 */
class Helper
{
    private $authKey;

    private $client;

    /**
     * Construct system helper service
     */
    public function __construct()
    {
        $this->authKey = file_get_contents('/liman/keys/service.key');
        $this->client = new Client([
            'base_uri' => 'http://127.0.0.1:3008',
        ]);
    }

    /**
     * Create linux user
     *
     * @param $extension_id
     * @return bool
     * @throws GuzzleException
     */
    public function userAdd($extension_id): bool
    {
        try {
            $this->client->get('/userAdd', [
                'query' => [
                    'extension_id' => str_replace('-', '', (string) $extension_id),
                    'liman_token' => $this->authKey,
                ],
            ]);
        } catch (\Exception) {
            return false;
        }

        return true;
    }

    /**
     * Remove linux user
     *
     * @param $extension_id
     * @return bool
     * @throws GuzzleException
     */
    public function userRemove($extension_id): bool
    {
        try {
            $this->client->get('/userRemove', [
                'query' => [
                    'extension_id' => str_replace('-', '', (string) $extension_id),
                    'liman_token' => $this->authKey,
                ],
            ]);
        } catch (\Exception) {
            return false;
        }

        return true;
    }

    /**
     * Update dns
     *
     * @param $server1
     * @param $server2
     * @param $server3
     * @return bool
     * @throws GuzzleException
     */
    public function dnsUpdate($server1, $server2, $server3): bool
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
        } catch (\Exception) {
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

    /**
     * Add certificate to Liman
     *
     * @param $tmpPath
     * @param $targetName
     * @return bool
     * @throws GuzzleException
     */
    public function addCertificate($tmpPath, $targetName): bool
    {
        $contents = $tmpPath;
        if (is_file($tmpPath)) {
            $contents = file_get_contents($tmpPath);
        } else {
            $tmpPath = '/tmp/' . str_random(16);
            file_put_contents($tmpPath, $contents);
        }
        $arr = [
            'certificate' => $contents,
            'targetName' => $targetName,
            'updatedAt' => Carbon::now()->toDateTimeString()
        ];

        $current = SystemSettings::where('key', 'SYSTEM_CERTIFICATES')->first();

        if ($current) {
            $foo = json_decode((string) $current->data, true);
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
        } catch (\Exception) {
            return false;
        }

        return true;
    }

    /**
     * Remove certificate from Liman
     *
     * @param $targetName
     * @return bool
     * @throws GuzzleException
     */
    public function removeCertificate($targetName): bool
    {
        $arr = [
            'targetName' => $targetName,
        ];

        $current = SystemSettings::where('key', 'SYSTEM_CERTIFICATES')->first();

        if ($current) {
            $foo = json_decode((string) $current->data, true);
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
        } catch (\Exception) {
            return false;
        }

        return true;
    }

    /**
     * Fix extension file permissions
     *
     * @param $extension_id
     * @param $extension_name
     * @return bool
     * @throws GuzzleException
     */
    public function fixExtensionPermissions($extension_id, $extension_name): bool
    {
        try {
            $this->client->get('/fixPermissions', [
                'query' => [
                    'liman_token' => $this->authKey,
                    'extension_id' => $extension_id,
                    'extension_name' => strtolower((string) $extension_name),
                ],
            ]);
        } catch (\Exception) {
            return false;
        }

        return true;
    }

    /**
     * Install packages to Liman server
     *
     * @param $packages
     * @return bool
     * @throws GuzzleException
     */
    public function installPackages($packages): bool
    {
        try {
            $this->client->get('/installPackages', [
                'query' => [
                    'liman_token' => $this->authKey,
                    'packages' => $packages,
                ],
            ]);
        } catch (\Exception) {
            return false;
        }

        return true;
    }

    /**
     * Run command on Liman server
     *
     * @param $command
     * @return array|Application|Translator|string|null
     * @throws GuzzleException
     */
    public function runCommand($command)
    {
        try {
            $response = $this->client->get('/extensionRun', [
                'query' => [
                    'liman_token' => $this->authKey,
                    'command' => $command,
                ],
            ]);
        } catch (\Exception) {
            return __('Liman Sistem Servisine Erişilemiyor!');
        }

        return $response->getBody()->getContents();
    }
}
