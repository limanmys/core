<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExtensionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $extension,$server,$user,$function,$parameters;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($server,$extension,$user,$function,$parameters)
    {
        $this->extension = $extension;
        $this->server = $server;
        $this->user = $user;
        $this->function = $function;
        $this->parameters = $parameters;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $parameters = json_decode($this->parameters);
        foreach($parameters as $key=>$param){
            request()->add([$key=>$param]);
        }
        $command = generateSandboxCommand($this->server, $this->extension, $this->extension->id,$this->user->id, "null", "null", $this->function);

        $output = shell_exec($command);
        
        system_log(7,"EXTENSION_BACKGROUND_RUN",[
            "extension_id" => $this->extension->id,
            "server_id" => $this->server->id,
            "target_name" => $this->function
        ]);

        $code = 200;
        try{
            $json = json_decode($output,true);
            if(array_key_exists("status",$json)){
                $code = intval($json["status"]);
            }
        }catch (\Exception $exception){};
        if(strval($code) == "200" && $json["message"] != ""){
            return true;
        }else{
            return false;
        }
    }
}
