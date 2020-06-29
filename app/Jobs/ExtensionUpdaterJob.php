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

class ExtensionUpdaterJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    private $extension;
    private $download;
    private $version_code;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($extension_id,$version_code, $download)
    {
        $this->extension = Extension::find($extension_id);
        $this->version_code = $version_code;
        $this->download = $download;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $downloadPath = "/tmp/" . $this->extension->id . "-" . $this->version_code;
        if(is_file($downloadPath)){
            return true;
        }
        $client = new Client([
            "headers" => [
                "Authorization" => "Bearer " . env("MARKET_ACCESS_TOKEN")
            ],
            "verify" => false
        ]);
        $resource = fopen($downloadPath, 'w');
        $client->request('GET', $this->download, ['sink' => $resource]);
        if(is_file($downloadPath)){
            AdminNotification::create([
                "title" => $this->extension->display_name . " Güncellemesi İndirildi!",
                "type" => "extension_update",
                "message" =>
                    $this->extension->display_name . " eklentisinin yeni bir sürümü indirildi, yüklemek için <a href='" . route('extensions_settings') . "?showUpdates" . "'>tıklayınız.</a>",
                "level" => 3,
            ]);
            return true;
        }else{
            return false;
        }
    }
}
