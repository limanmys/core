<?php

namespace App\Jobs;

use App\Models\Liman;
use App\Models\SystemSettings;
use App\System\Command;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use ZipArchive;

/**
 * High Availability Syncer
 * Syncs Limans in circular type and keeps all of them up to date.
 *
 * @extends ShouldQueue
 */
class HighAvailabilitySyncer implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws GuzzleException
     */
    public function handle()
    {
        if (! (bool) env('HIGH_AVAILABILITY_MODE', false)) {
            return;
        }

        $system = rootSystem();

        $localIp = Command::runSystem("hostname -I | cut -d' ' -f1");

        $limans = Liman::all()->pluck(["last_ip"])->toArray();
        $updateInformations = [];
        foreach ($limans as $ip) {
            if ($localIp == $ip)
                continue;

            $updateInformations[] = $this->fetchUpdateInformation($ip);
        }

        foreach ($updateInformations as $information) {
            foreach ($information['missing_extensions'] as $extension) {
                $this->installExtension($extension);
            }

            foreach ($information['update_extensions'] as $extension) {
                $this->updateExtension($extension);
            }
        }

        receiveSystemSettings();
        updateSystemSettings();

        $dns = SystemSettings::where([
            'key' => 'SYSTEM_DNS',
        ])->first();
        if ($dns) {
            $json = json_decode((string) $dns->data);
            $system->dnsUpdate($json[0], $json[1], $json[2]);
        }

        $certificates = SystemSettings::where([
            'key' => 'SYSTEM_CERTIFICATES',
        ])->first();
        if ($certificates) {
            $json = json_decode((string) $certificates->data, true);
            foreach ($json as $cert) {
                if (
                    is_file(
                        '/usr/local/share/ca-certificates/' .
                        $cert['targetName'] .
                        '.crt'
                    )
                    ||
                    is_file(
                        '/etc/pki/ca-trust/source/anchors/' .
                        $cert['targetName'] .
                        '.crt'
                    )
                ) {
                    continue;
                }
                $system->addCertificate(
                    $cert['certificate'],
                    $cert['targetName']
                );
            }
        }

        Liman::where(['last_ip' => $localIp])->first()->touch();
    }

    /**
     * Get version information from other Liman
     *
     * @param $ip
     * @return array|array[]
     * @throws GuzzleException
     */
    private function fetchUpdateInformation($ip)
    {
        $client = new Client([
            'verify' => false
        ]);

        $extensionListResponse = $client->request('GET', 'https://' . $ip . '/hasync/extension_list');
        $extensionList = json_decode($extensionListResponse->getBody()->getContents());
        $missingExtensionList = [];
        $needsToBeUpdated = [];
        
        foreach ($extensionList as $extension) {
            $path = '/liman/extensions/' . $extension->name;

            // Determine if folder does exist
            if (!is_dir($path)) {
                $missingExtensionList[] = $extension;
                continue;
            }

            // If exists check if up to date
            if (is_file($path . '/db.json')) {
                $json = (array) json_decode(file_get_contents($path . '/db.json'));

                $version = (int) str_replace('.', '', $json['version']);

                if ($version < $extension->version_code) {
                    $needsToBeUpdated[] = $extension;
                    continue;
                }
            }
        }

        return [
            "missing_extensions" => $missingExtensionList,
            "update_extensions" => $needsToBeUpdated
        ];
    }

    /**
     * Install not existing extension
     *
     * @param $extension
     * @throws GuzzleException
     * @return void
     */
    private function installExtension($extension)
    {
        $system = rootSystem();
        $extension = (array) $extension;

        // Download extension and put to the folder
        $file = $this->downloadFile($extension['download_path']);

        if (!$file || !is_file($file)) {
            throw new Exception("file could not be downloaded");
        }

        $zip = new ZipArchive();

        if (!$zip->open($file)) {
            throw new Exception("downloaded zip file cannot be opened");
        }

        $path = '/tmp/' . Str::random();
        try {
            $zip->extractTo($path);
        } catch (\Exception) {
            throw new Exception("error when extracting zip file");
        }

        $extension_folder = '/liman/extensions/' . strtolower((string) $extension['name']);

        Command::runLiman('mkdir -p @{:extension_folder}', [
            'extension_folder' => $extension_folder,
        ]);

        Command::runLiman('cp -r {:path}/* {:extension_folder}/.', [
            'extension_folder' => $extension_folder,
            'path' => $path,
        ]);

        Command::runSystem('rm -rf @{:file}', [
            'file' => $path
        ]);

        // Create linux user for sandbox
        $system->userAdd($extension['id']);

        // Create key file and fill the content
        $passPath = '/liman/keys' . DIRECTORY_SEPARATOR . $extension['id'];
        Command::runSystem('chmod 760 @{:path}', [
            'path' => $passPath,
        ]);
        file_put_contents($passPath, $extension['key_content']);

        // Fix permissions
        $system->fixExtensionPermissions($extension['id'], $extension['name']);

        $json = getExtensionJson($extension['name']);
        if (
            array_key_exists('dependencies', $json) &&
            $json['dependencies'] != ''
        ) {
            $system->installPackages($json['dependencies']);
        }

        Command::runSystem('rm -rf @{:file}', [
            'file' => $file
        ]);
    }

    /**
     * Download file and return path
     *
     * @param $url
     * @param $format
     * @return string
     */
    private function downloadFile($url, $format = "zip")
    {
        $client = new Client([
            'verify' => false
        ]);

        $path = '/tmp/' . Str::random(32) . '.' . $format;

        $resource = fopen($path, 'w');
        try {
            $client->request('GET', $url, ['sink' => $resource]);
        } catch (\Throwable $e) {
            return "";
        }

        return $path;
    }

    /**
     * Update old extension
     *
     * @param $extension
     * @throws GuzzleException
     * @return void
     */
    private function updateExtension($extension)
    {
        $system = rootSystem();
        $extension = (array) $extension;

        // Download extension and put to the folder
        $file = $this->downloadFile($extension['download_path']);

        if (!$file || !is_file($file)) {
            throw new Exception("file could not be downloaded");
        }

        $zip = new ZipArchive();

        if (!$zip->open($file)) {
            throw new Exception("downloaded zip file cannot be opened");
        }

        $path = '/tmp/' . Str::random();
        try {
            $zip->extractTo($path);
        } catch (\Exception) {
            throw new Exception("error when extracting zip file");
        }

        $extension_folder = '/liman/extensions/' . strtolower((string) $extension['name']);

        Command::runLiman('mkdir -p @{:extension_folder}', [
            'extension_folder' => $extension_folder,
        ]);

        Command::runLiman('cp -r {:path}/* {:extension_folder}/.', [
            'extension_folder' => $extension_folder,
            'path' => $path,
        ]);

        Command::runSystem('rm -rf @{:file}', [
            'file' => $path
        ]);

        Command::runSystem('rm -rf @{:file}', [
            'file' => $file
        ]);

        // Fix permissions
        $system->fixExtensionPermissions($extension['id'], $extension['name']);

        $json = getExtensionJson($extension['name']);
        if (
            array_key_exists('dependencies', $json) &&
            $json['dependencies'] != ''
        ) {
            $system->installPackages($json['dependencies']);
        }
    }
}
