<?php

namespace App\Jobs;

use App\Models\Liman;
use App\System\Command;
use Carbon\Carbon;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use ZipArchive;

class HighAvailabilitySyncer implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $client;
    private $system;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->client = new Client([
            'verify' => 'false'
        ]);
        $this->system = rootSystem();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if (! (bool) env('HIGH_AVAILABILITY_MODE', false)) {
            return;
        }

        $limans = Liman::all()->pluck(["last_ip"])->toArray();
        $updateInformations = [];
        foreach ($limans as $ip) {
            if ($_SERVER['SERVER_ADDR'] == $ip) 
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

            foreach ($information['missing_modules'] as $module) {
                $this->installModule($module);
            }

            foreach ($information['update_modules'] as $module) {
                $this->installModule($module);
            }
        }

        receiveSystemSettings();
        updateSystemSettings();
    }

    /**
     * Get version information from other Liman
     */
    private function fetchUpdateInformation($ip)
    {
        if ($_SERVER['SERVER_ADDR'] == $ip) {
            return [
                "missing_extensions" => [],
                "update_extensions" => [],
                "missing_modules" => [],
                "update_modules" => []
            ];
        }

        $extensionListResponse = $this->client->request('GET', 'https://' . $ip . '/hasync/extension_list');
        $moduleListResponse = $this->client->request('GET', 'https://' . $ip . '/hasync/module_list');

        $extensionList = json_decode($extensionListResponse->getBody()->getContents());
        $moduleList = json_decode($moduleListResponse->getBody()->getContents());

        $missingExtensionList = [];
        $needsToBeUpdated = [];
        foreach ($extensionList as $extension) {
            $path = '/liman/extensions/' . $extension['name'];

            // Determine if folder does exist
            if (!is_dir($path)) {
                $missingExtensionList[] = $extension;
                continue;
            }

            // If exists check if up to date
            if (is_file($path . '/db.json')) {
                $json = json_decode(file_get_contents($path . '/db.json'));

                $version = (int) str_replace('.', '', $json['version']);

                if ($version < $extension['version_code']) {
                    $needsToBeUpdated[] = $extension;
                    continue;
                }
            }
        }

        $missingModuleList = [];
        $moduleNeedsToBeUpdated = [];
        foreach ($moduleList as $module) {
            $path = '/liman/modules/' . $module['name'];

            // Determine if module does exist
            if (!is_dir($path)) {
                $missingModuleList[] = $module;
                continue;
            }

            // If module is up to date
            $updatedAt = Carbon::parse($module['updated_at'])->getTimestamp();
            if ($updatedAt > filemtime($path)) {
                $moduleNeedsToBeUpdated[] = $module;
                continue;
            }
        }

        return [
            "missing_extensions" => $missingExtensionList,
            "update_extensions" => $needsToBeUpdated,
            "missing_modules" => $missingModuleList,
            "update_modules" => $moduleNeedsToBeUpdated
        ];
    }

    /**
     * Install not existing extension
     */
    private function installExtension($extension)
    {
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

        // Create linux user for sandbox
        $this->system->userAdd($extension['id']);

        // Create key file and fill the content
        $passPath = '/liman/keys' . DIRECTORY_SEPARATOR . $extension['id'];
        Command::runSystem('chmod 760 @{:path}', [
            'path' => $passPath,
        ]);
        file_put_contents($passPath, $extension['key_content']);

        // Fix permissions
        $this->system->fixExtensionPermissions($extension['id'], $extension['name']);

        $json = getExtensionJson($extension['name']);
        if (
            array_key_exists('dependencies', $json) &&
            $json['dependencies'] != ''
        ) {
            $this->system->installPackages($json['dependencies']);
        }
    }

    /**
     * Update old extension
     */
    private function updateExtension($extension)
    {
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

        // Fix permissions
        $this->system->fixExtensionPermissions($extension['id'], $extension['name']);

        $json = getExtensionJson($extension['name']);
        if (
            array_key_exists('dependencies', $json) &&
            $json['dependencies'] != ''
        ) {
            $this->system->installPackages($json['dependencies']);
        }
    }

    /**
     * Install not existing module
     */
    private function installModule($module)
    {
        // Download module and put to the folder
        $file = $this->downloadFile($module['download_path']);

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

        $module_folder = '/liman/modules/' . (string) $module['name'];

        Command::runLiman('mkdir -p @{:module_folder}', [
            'module_folder' => $module_folder,
        ]);

        Command::runLiman('cp -r {:path}/* {:module_folder}/.', [
            'module_folder' => $module_folder,
            'path' => $path,
        ]);

        Artisan::call("module:add " . $module['name']);
    }

    /**
     * Update old module
     */
    private function updateModule($module)
    {
        // Download module and put to the folder
        $file = $this->downloadFile($module['download_path']);

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

        $module_folder = '/liman/modules/' . (string) $module['name'];

        Command::runLiman('mkdir -p @{:module_folder}', [
            'module_folder' => $module_folder,
        ]);

        Command::runLiman('cp -r {:path}/* {:module_folder}/.', [
            'module_folder' => $module_folder,
            'path' => $path,
        ]);

        Artisan::call("module:add " . $module['name']);
    }

    /**
     * Download file and return path
     */
    private function downloadFile($url, $format = null)
    {
        $path = '/tmp/' . Str::random(32) . '.' . $format;

        $resource = fopen($path, 'w');
        try {
            $this->client->request('GET', $url, ['sink' => $resource]);
        } catch (\Throwable $e) {
            return "";
        }

        return $path;
    }
}
