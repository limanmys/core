<?php

namespace App\Jobs;

use App\Http\Controllers\Extension\MainController;
use App\Models\Extension;
use App\System\Command;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Extension Updater Job
 *
 * @implements ShouldQueue
 */
class ExtensionUpdaterJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $extension;

    private $retry = 3;

    private $signed = false;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        $extension_id,
        private $version_code,
        private $download,
        private $hash,
        private $forceUpdate = false
    )
    {
        $this->extension = Extension::find($extension_id);
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws GuzzleException
     * @throws GuzzleException
     */
    public function handle()
    {
        $downloadPath =
            '/tmp/' . $this->extension->id . '-' . $this->version_code;
        $exists = Command::runLiman('[ -e @{:downloadPath} ] && echo 1 || echo 0', [
            'downloadPath' => $downloadPath,
        ]);
        $flag = true;
        $fileHash = Command::runLiman("sha512sum @{:downloadPath} 2>/dev/null | cut -d ' ' -f 1", [
            'downloadPath' => $downloadPath,
        ]);
        if ($exists != '1' || $fileHash != $this->hash) {
            $flag = self::downloadFile($downloadPath);
        }

        if ($flag && $this->forceUpdate) {
            $controller = new MainController();
            [$flag, $extension] = $controller->setupNewExtension(
                $downloadPath
            );
           
            self::updateUpdatesFile();
        }

        return $flag;
    }

    /**
     * Download file from Liman Market
     *
     * @param $downloadPath
     * @return bool
     * @throws GuzzleException
     */
    private function downloadFile($downloadPath)
    {
        $client = new Client([
            'headers' => [
                'Authorization' => 'Bearer ' . env('MARKET_ACCESS_TOKEN'),
            ],
            'verify' => false,
        ]);
        $resource = fopen($downloadPath, 'w');
        $response = $client->request('GET', $this->download, ['sink' => $resource]);
        try {
            $headers = $response->getHeaders();
            $headers = array_change_key_case($headers, CASE_LOWER);

            $str = $headers['content-disposition'][0];
            $arr = explode(';', (string) $str);
            if (substr($arr[1], -7) == '.signed') {
                $this->signed = true;
            }
        } catch (\Exception) {
            return false;
        }

        $fileHash = Command::runLiman("sha512sum @{:downloadPath} | cut -d ' ' -f 1", [
            'downloadPath' => $downloadPath,
        ]);
        if (is_file($downloadPath) && $fileHash == $this->hash) {
            if ($this->signed) {
                $tmp2 = '/tmp/' . str_random();
                Command::runLiman(
                    'gpg --status-fd 1 -d -o @{:tmp2} @{:downloadPath} >/dev/null 2>/dev/null', [
                        'tmp2' => $tmp2,
                        'downloadPath' => $downloadPath,
                    ]
                );
                Command::runLiman('mv @{:tmp2} @{:downloadPath}', [
                    'tmp2' => $tmp2,
                    'downloadPath' => $downloadPath,
                ]);
            }

            return true;
        } else {
            $this->retry = $this->retry - 1;
            if ($this->retry < 0) {
                return false;
            } else {
                return self::downloadFile($downloadPath);
            }
        }
    }

    /**
     * Update extension updates file
     *
     * @return void
     */
    private function updateUpdatesFile()
    {
        $json = array_values(json_decode(
            file_get_contents(storage_path('extension_updates')),
            true
        ));
        for ($i = 0; $i < count($json); $i++) {
            if ($json[$i]['extension_id'] = $this->extension->id) {
                unset($json[$i]);
            }
        }
        if (count($json)) {
            file_put_contents(
                storage_path('extension_updates'),
                json_encode($json)
            );
        } else {
            unlink(storage_path('extension_updates'));
        }
    }
}
