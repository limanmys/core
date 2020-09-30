<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\Extension;
use App\Models\AdminNotification;
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
    private $hash;
    private $retry = 3;
    private $signed = false;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        $extension_id,
        $version_code,
        $download,
        $hash,
        $forceUpdate = false
    ) {
        $this->extension = Extension::find($extension_id);
        $this->version_code = $version_code;
        $this->download = $download;
        $this->forceUpdate = $forceUpdate;
        $this->hash = $hash;
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
        $fileHash = trim(shell_exec("sha512sum $downloadPath 2>/dev/null | cut -d ' ' -f 1"));
        if ($exists != "1" || $fileHash != $this->hash) {
            $flag = self::downloadFile($downloadPath);
        }

        if ($flag && $this->forceUpdate) {
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
        $response = $client->request('GET', $this->download, ['sink' => $resource]);
        try{
            $str = $response->getHeaders()["Content-Disposition"][0];
            $arr = explode(";",$str);
            if (substr($arr[1],-7) == 'signed"') {
                $this->signed = true;
            }
        }catch(\Exception $e){
            return false;
        }

        $fileHash = trim(shell_exec("sha512sum $downloadPath | cut -d ' ' -f 1"));
        if (is_file($downloadPath) && $fileHash == $this->hash) {
            if ($this->signed) {
                $tmp2 = "/tmp/" . str_random();
                shell_exec(
                    "gpg --status-fd 1 -d -o '" . $tmp2 . "' " . $downloadPath . " >/dev/null 2>/dev/null"
                );
                shell_exec("mv " . $tmp2 . " " . $downloadPath);
            }
            return true;
        } else {
            $this->retry = $this->retry -1;
            if($this->retry < 0 ){
                return false;
            } else{
                return self::downloadFile($downloadPath);
            }
        }
    }

    private function updateUpdatesFile()
    {
        $json = array_values(json_decode(
            file_get_contents(storage_path('extension_updates')),
            true
        ));
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
