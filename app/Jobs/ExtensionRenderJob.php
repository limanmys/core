<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExtensionRenderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    private $command;

    /**
     * Create a new job instance.
     *
     * @param $command
     */
    public function __construct($command)
    {
        $this->command = $command;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $output = shell_exec($this->command);
        file_put_contents(storage_path("extension_cache/") . $this->job->getJobId(),$output);
    }

    public function getJobId()
    {
        return $this->job->getJobId();
    }
}
