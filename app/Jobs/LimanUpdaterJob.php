<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use GuzzleHttp\Client;
use App\Models\AdminNotification;

class LimanUpdaterJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $downloadUrl;
    protected $downloadTo;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($version, $downloadUrl)
    {
        $this->downloadUrl = $downloadUrl;
        $this->downloadTo = "/liman/packages/liman-$version.deb";
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        self::downloadFile();
    }

    private function downloadFile()
    {
        if (is_file($this->downloadTo)) {
            return true;
        }

        $client = new Client([
            "headers" => [
                "Authorization" => "Bearer " . env("MARKET_ACCESS_TOKEN"),
            ],
            "verify" => false,
        ]);
        $resource = fopen($this->downloadTo, 'w');
        try{
            $client->request('GET', $this->downloadUrl, ['sink' => $resource]);
        }catch(\Exception $e){
            AdminNotification::create([
                "title" =>
                    $this->extension->display_name . " eklentisinin güncellemesi indirilemedi!",
                "type" => "error",
                "message" =>
                    "Oluşan hata : " . $e->getMessage(),
                "level" => 3,
            ]);
            return false;
        }

        AdminNotification::create([
            "title" =>
                $this->extension->display_name . " eklentisinin güncellemesi indirildi!",
            "type" => "",
            "message" =>
                $this->extension->display_name .
                " eklentisinin güncellemesi başarıyla indirildi, eklentiler sekmesi üzerinden değişim kaydını görebilir, eklentiyi güncelleyebilirsiniz.",
            "level" => 3,
        ]);
        
        if (is_file($this->downloadTo)) {
            return true;
        } else {
            return false;
        }
    }
}
