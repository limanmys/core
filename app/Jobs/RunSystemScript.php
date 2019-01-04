<?php

namespace App\Jobs;

use App\Extension;
use App\Script;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class RunSystemScript implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $extension, $script, $server;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($extension, $script, $server)
    {
        $this->script = $script;
        $this->server = $server;
        $this->extension = $extension;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $output = $this->server->runScript($this->script, \request('domain') . " " . \request('interface'));
        if ($this->server->isRunning($this->extension->service) == "active\n") {
            $this->server->extensions = array_merge($this->server->extensions, [\request('extension')]);
            $this->server->save();
        }
    }
}
