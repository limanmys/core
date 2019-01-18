<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class RunScript implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $script,$server,$user, $parameters, $notification, $extension, $key;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(\App\Script $script, $server, $parameters, $user, \App\Notification $notification,\App\Extension $extension)
    {
        $this->user = $user;
        $this->server = $server;
        $this->key = $server->key;
        $this->script = $script;
        $this->parameters = $parameters;
        $this->notification = $notification;
        $this->extension = $extension;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->notification->type = "working";
        $this->read = false;
        $this->notification->save();
        //Copy script to target.
        $copy_file_query = 'scp -P ' . $this->server->port . " -i " . storage_path('keys') . DIRECTORY_SEPARATOR . $this->user->_id .' ' . storage_path('app/scripts/' . $this->script->_id) .' ' . $this->key->username .'@' . $this->server->ip_address . ':/tmp/';
        shell_exec($copy_file_query);
        $permission_query = 'sudo chmod +x /tmp/' . $this->script->_id;
        $query = "ssh -p " . $this->server->port . " " . $this->key->username . "@" . $this->server->ip_address
        . " -i "  . storage_path('keys') . DIRECTORY_SEPARATOR . $this->user->_id . " " . $permission_query . " 2>&1";
        shell_exec($query);
        $query = ($this->script->root == 1)? 'sudo ' : '';
        $query = $query . substr($this->script->language,1) . ' /tmp/' .$this->script->_id . " run ".$this->parameters;
        $query = "ssh -p " . $this->server->port . " " . $this->key->username . "@" . $this->server->ip_address
            . " -i "  . storage_path('keys') . DIRECTORY_SEPARATOR . $this->user->_id . " " . $query . " 2>&1";
        shell_exec($query);
        $service_status = "sudo systemctl is-failed " . $this->extension->service;
        $query = "ssh -p " . $this->server->port . " " . $this->key->username . "@" . $this->server->ip_address
            . " -i "  . storage_path('keys') . DIRECTORY_SEPARATOR . $this->user->_id . " " . $service_status . " 2>&1";
        $log = shell_exec($query);
        if ($log == "active\n") {
            $this->server->extensions = array_merge($this->server->extensions, [$this->extension->_id]);
            $this->server->save();
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

    public function failed(){
        $this->notification->type = "error";
        $this->notification->title = "Hata Oluştu";
        $this->notification->message = $this->server->name . " sunucusunda hata oluştu.";
        $this->notification->log = $this->output;
        $this->notification->query = $this->query;
        $this->notification->save();
    }
}
