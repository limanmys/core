<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use App\Extension;
use App\AdminNotification;
use Illuminate\Queue\SerializesModels;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use App\Http\Controllers\Extension\MainController;

class ExtensionUpdaterJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    private $extension;
    private $download;
    private $version_code;
    private $forceUpdate;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        $extension_id,
        $version_code,
        $download,
        $forceUpdate = false
    ) {
        $this->extension = Extension::find($extension_id);
        $this->version_code = $version_code;
        $this->download = $download;
        $this->forceUpdate = $forceUpdate;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $downloadPath =
            "/tmp/" . $this->extension->id . "-" . $this->version_code;
        $exists = trim(
            shell_exec("[ -e '$downloadPath' ] && echo 1 || echo 0")
        );
        $flag = true;

        if ($exists != "1") {
            $flag = self::downloadFile($downloadPath);
        }

        if ($this->forceUpdate) {
            $controller = new MainController();
            list($flag, $extension) = $controller->setupNewExtension(
                $downloadPath
            );
            AdminNotification::create([
                "title" =>
                    $this->extension->display_name . " eklentisi güncellendi!",
                "type" => "extension_update",
                "message" =>
                    $this->extension->display_name .
                    " eklentisinin yeni bir sürümü indirildi ve yüklendi. İncelemek için için <a href='" .
                    route('settings') .
                    "#extensions" .
                    "'>tıklayınız.</a>",
                "level" => 3,
            ]);
            self::updateUpdatesFile();
        }

        return $flag;
    }

    private function downloadFile($downloadPath)
    {
        $client = new Client([
            "headers" => [
                "Authorization" => "Bearer " . env("MARKET_ACCESS_TOKEN"),
            ],
            "verify" => false,
        ]);
        $resource = fopen($downloadPath, 'w');
        $client->request('GET', $this->download, ['sink' => $resource]);
        if (is_file($downloadPath)) {
            return true;
        } else {
            return false;
        }
    }

    private function updateUpdatesFile()
    {
        $json = json_decode(
            file_get_contents(storage_path('extension_updates')),
            true
        );
        for ($i = 0; $i < count($json); $i++) {
            if ($json[$i]["extension_id"] = $this->extension->id) {
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
