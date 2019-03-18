<?php

namespace App\Jobs;

use App\Classes\Connector\SSHConnector;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

/**
 * Class InstallService
 * @package App\Jobs
 */
class InstallService implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    /**
     * @var \App\Script
     */
    /**
     * @var \App\Script
     */
    /**
     * @var \App\Script
     */
    /**
     * @var \App\Script
     */
    /**
     * @var \App\Notification|\App\Script
     */
    /**
     * @var \App\Extension|\App\Notification|\App\Script
     */
    /**
     * @var \App\Extension|\App\Notification|\App\Script
     */
    protected $script,$server,$user_id, $parameters, $notification, $extension;


    /**
     * InstallService constructor.
     * @param \App\Script $script
     * @param $server
     * @param $parameters
     * @param $user_id
     * @param \App\Notification $notification
     * @param \App\Extension $extension
     */
    public function __construct(\App\Script $script, $server, $parameters, $user_id, \App\Notification $notification, \App\Extension $extension)
    {
        $this->user_id = $user_id;
        $this->server = $server;
        $this->script = $script;
        $this->parameters = $parameters;
        $this->notification = $notification;
        $this->extension = $extension;
    }

    /**
     * @throws \Throwable
     */
    public function handle()
    {
        $this->notification->type = "working";
        $this->notification->read = false;
        $this->notification->save();

        $ssh = new SSHConnector($this->server, $this->user_id);

        $ssh->runScript($this->script,$this->parameters);
        $status = $ssh->execute("(systemctl list-units | grep " . $this->extension->service . "  && echo \"OK\" || echo \"NOK\") | tail -1");
        $extensions_array = $this->server->extensions;
        $extensions_array[$this->extension->_id] = [];
        $this->server->extensions = $extensions_array;
        $this->server->save();
        if ($status == "OK\n") {
            $this->notification->type = "success";
            $this->notification->title = $this->extension->name . " kuruldu";
            $this->notification->message = $this->extension->name . " servisi kurulumu başarıyla tamamlandı.";
        }else{
            $this->notification->type = "error";
            $this->notification->title = $this->extension->name . " kurulamadı";
            $this->notification->message = $this->extension->name . " servisi kurulumu başarısız.";
        }
        $this->notification->save();
    }

    /**
     *
     */
    public function failed(){
        $this->notification->type = "error";
        $this->notification->title = "Hata Oluştu";
        $this->notification->message = $this->server->name . " sunucusunda hata oluştu.";
        $this->notification->log = $this->output;
        $this->notification->query = $this->query;
        $this->notification->save();
    }
}
