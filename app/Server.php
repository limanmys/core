<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use Illuminate\Support\Facades\Auth;

class Server extends Eloquent
{
    protected $collection = 'servers';
    protected $connection = 'mongodb';
    protected $fillable = ['name', 'ip_address' ,'port' ,'city'];
    private $key = null;
    public function run($command){

        $key = Key::where([
            'server_id' => $this->id,
            'user_id' => Auth::id()
        ])->first();
        $query = "ssh -p " . $this->port . " " . $key->username . "@" . $this->ip_address . " -i ../keys/" .
            Auth::id() . " " . $command . " 2>&1";
        return shell_exec($query);
    }

    public function runScript($script,$parameters){
        $key = Key::where([
            'server_id' => $this->id,
            'user_id' => Auth::id()
        ])->first();
        //Copy script to target.
        $copy_file_query = 'scp -P ' . $this->port . " -i ../keys/" . Auth::id() .' ' . storage_path('app/scripts/' . $script->_id) .' ' . $key->username .'@' . $this->ip_address . ':/tmp/';
        shell_exec($copy_file_query);
        shell_exec('sudo chmod +x /tmp/' . $script->_id);
        $query = ($script->root == 1)? 'sudo ' : '';
        $query = $query . substr($script->language,1) . ' /tmp/' .$script->_id . " run".$parameters;
        $query = $query = "ssh -p " . $this->port . " " . $key->username . "@" . $this->ip_address . " -i ../keys/" .
            Auth::id() . " " . $query . " 2>&1";
        $output = shell_exec($query);
        return $output;
    }

    public function isRunning($service_name){
        $key = Key::where([
            'server_id' => $this->id,
            'user_id' => Auth::id()
        ])->first();
        $query = "sudo systemctl is-failed " .$service_name;
        $query = $query = "ssh -p " . $this->port . " " . $key->username . "@" . $this->ip_address . " -i ../keys/" .
            Auth::id() . " " . $query . " 2>&1";
        return shell_exec($query);
    }

    public function integrity(){
        //First, let's check if user has authority to use this server.
        //Let's check if ssh is enabled and user has access to it.
        if($this->sshAccessEnabled() == false){
            return false;
        }
        return true;
    }

    private function changeHostname(){

    }

    public function sshPortEnabled(){
        $output = shell_exec("echo exit | telnet " . $this->ip_address ." " . $this->port);
        if (strpos($output,"Connected to " . $this->ip_address)){
            return true;
        }else{
            return false;
        }
    }

    private function sshAccessEnabled(){
        $key = $this->sshKey();
        $this->key = $key;
        if(!$this->sshPortEnabled() || !$key){
            return false;
        }
        return true;
    }

    private function sshKey(){
        $key = Key::where([
            'server_id' => $this->id,
            'user_id' => Auth::id()
        ])->first();
        if($key == null){
            return false;
        }
        $query = "ssh -p " . $this->port . " " . $key->username . "@" . $this->ip_address . " -i ../keys/" .
            Auth::id() . " " . "whoami" . " 2>&1";
        $output = shell_exec($query);
        if($output != ($key->username . "\n")){
            return false;
        }else{
            return $key;
        }
    }
}