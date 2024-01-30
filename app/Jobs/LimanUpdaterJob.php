<?php

namespace App\Jobs;

use App\Models\AdminNotification;
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

    protected string $downloadTo;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(protected string $version, protected string $downloadUrl)
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
        $this->downloadFile();
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
                'Authorization' => 'Bearer ' . config('services.market.access_token'),
            ],
            'verify' => false,
        ]);

        try {
            $response = $client->request('GET', $this->downloadUrl);

            if ($response->getStatusCode() == 200) {
                file_put_contents($this->downloadTo, $response->getBody());
                $this->notifySuccess();
                return true;
            }
        } catch (\Exception $e) {
            $this->notifyError($e->getMessage());
            return false;
        }

        return false;
    }

    /**
     * Notify success message
     *
     * @return void
     */
    private function notifySuccess()
    {
        AdminNotification::create([
            'title' => json_encode([
                'tr' => $this->getNotificationMessage('Güncelleme Başarılı', 'tr'),
                'en' => $this->getNotificationMessage('Update Successful', 'en'),
            ]),
            'type' => 'success',
            'message' => json_encode([
                'tr' => $this->getNotificationMessage('Güncelleme başarıyla tamamlandı.', 'tr'),
                'en' => $this->getNotificationMessage('Update completed successfully.', 'en'),
            ]),
            'level' => 3,
        ]);
    }

    /**
     * Notify error message
     *
     * @param string $errorMessage
     * @return void
     */
    private function notifyError(string $errorMessage)
    {
        AdminNotification::create([
            'title' => json_encode([
                'tr' => $this->getNotificationMessage('Güncelleme Hatası', 'tr'),
                'en' => $this->getNotificationMessage('Update Error', 'en'),
            ]),
            'type' => 'error',
            'message' => json_encode([
                'tr' => $this->getNotificationMessage('Oluşan hata: ', 'tr') . $errorMessage,
                'en' => $this->getNotificationMessage('Error occurred: ', 'en') . $errorMessage,
            ]),
            'level' => 3,
        ]);
    }

    /**
     * Get notification message
     *
     * @param string $message
     * @param string $locale
     * @return string
     */
    private function getNotificationMessage(string $message, string $locale): string
    {
        return $this->extension->display_name . __(' eklentisinin güncellemesi ', [], $locale) . $message;
    }
}
