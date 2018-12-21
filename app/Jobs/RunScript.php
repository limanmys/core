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
    private $script,$server,$user, $parameters;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($script,$server,$parameters,$user,$notification)
    {
        $this->user = $user;
        $this->server = $server;
        $this->script = $script;
        $this->parameters = $parameters;
        $this->notification = $notification;
        $notification->type = "onhold";
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $key = \App\Key::where([
            'server_id' => $this->server->_id,
            'user_id' => $this->user->_id
        ])->first();
        //Copy script to target.
        $copy_file_query = 'scp -P ' . $this->server->port . " -i ../keys/" . $this->user->_id .' ' . storage_path('app/scripts/' . $this->script->_id) .' ' . $key->username .'@' . $this->server->ip_address . ':/tmp/';
        shell_exec($copy_file_query);
        shell_exec('sudo chmod +x /tmp/' . $this->script->_id);
        $query = ($this->script->root == 1)? 'sudo ' : '';
        $query = $query . substr($this->script->language,1) . ' /tmp/' .$this->script->_id . " run ".$this->parameters;
        $query = $query = "ssh -p " . $this->server->port . " " . $key->username . "@" . $this->server->ip_address . " -i ../keys/" .
            Auth::id() . " " . $query . " 2>&1";
        $output = shell_exec($query);
        $this->notification->log = $output;
        $this->notification->type = "success";
    }
}
