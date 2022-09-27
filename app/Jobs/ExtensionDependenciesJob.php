<?php

namespace App\Jobs;

use App\Models\AdminNotification;
use App\System\Command;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExtensionDependenciesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $extension;

    private $dependencies;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($extension, $dependencies = '')
    {
        $this->extension = $extension;
        $this->dependencies = $dependencies;
        $this->extension->update([
            'status' => '0',
        ]);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $package = $this->dependencies;
        $tmp = '/tmp/'.str_random(16);
        $installCommand = "if [ -z '\$(find /var/cache/apt/pkgcache.bin -mmin -60)' ]; then sudo apt-get update; fi;DEBIAN_FRONTEND=noninteractive sudo apt-get install -o Dpkg::Use-Pty=0 -o Dpkg::Options::='--force-confdef' -o Dpkg::Options::='--force-confold' @{:package} -qqy --force-yes > @{:tmp} 2>&1";
        Command::runSystem($installCommand, [
            'package' => $package,
            'tmp' => $tmp,
        ]);
        $checkCommand = "dpkg --get-selections | grep -v deinstall | awk '{print $1}' | grep -xE @{:package}";
        $installed = Command::runSystem($checkCommand, [
            'package' => str_replace(' ', '|', $package),
        ]);
        $dep = explode(' ', $this->dependencies);
        sort($dep);
        $installed = explode("\n", trim($installed));
        sort($installed);

        if ($dep == $installed) {
            $this->extension->update([
                'status' => '1',
            ]);
            $this->extension->save();

            AdminNotification::create([
                'title' => json_encode([
                    'tr' => $this->extension->display_name.__(' eklentisi hazır!', [], 'tr'),
                    'en' => $this->extension->display_name.__(' eklentisi hazır!', [], 'en'),
                ]),
                'type' => '',
                'message' => json_encode([
                    'tr' => $this->extension->display_name.
                    __(' eklentisinin bağımlılıkları başarıyla yüklendi, hemen kullanmaya başlayabilirsiniz.', [], 'tr'),
                    'en' => $this->extension->display_name.
                    __(' eklentisinin bağımlılıkları başarıyla yüklendi, hemen kullanmaya başlayabilirsiniz.', [], 'en'),
                ]),
                'level' => 3,
            ]);
        } else {
            AdminNotification::create([
                'title' => json_encode([
                    'tr' => $this->extension->display_name.__(' eklentisi kurulamadı!', [], 'tr'),
                    'en' => $this->extension->display_name.__(' eklentisi kurulamadı!', [], 'en'),
                ]),
                'type' => 'error',
                'message' => $this->extension->display_name.
                    __(' eklentisinin bağımlılıkları yüklenemedi,').'detayları '.$tmp.' dosyasından inceleyebilirsiniz.',
                'level' => 3,
            ]);
        }
    }
}
