<?php

namespace App\Jobs;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Liman Updater Job
 *
 * @implements ShouldQueue
 */
class LimanUpdaterJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $downloadTo;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($version, protected $downloadUrl)
    {
        $this->downloadTo = "/liman/packages/liman-$version.deb";
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws GuzzleException
     */
    public function handle()
    {
        self::downloadFile();
    }

    /**
     * Download file from Market
     *
     * @return bool
     * @throws GuzzleException
     */
    private function downloadFile()
    {
        if (is_file($this->downloadTo)) {
            return true;
        }

        $client = new Client([
            'headers' => [
                'Authorization' => 'Bearer ' . env('MARKET_ACCESS_TOKEN'),
            ],
            'verify' => false,
        ]);
        $resource = fopen($this->downloadTo, 'w');
        try {
            $client->request('GET', $this->downloadUrl, ['sink' => $resource]);
        } catch (\Exception $e) {
            

            return false;
        }

        

        if (is_file($this->downloadTo)) {
            return true;
        } else {
            return false;
        }
    }
}
