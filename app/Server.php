<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use Illuminate\Support\Facades\Auth;

class Server extends Eloquent
{
    protected $collection = 'servers';
    protected $connection = 'mongodb';
    protected $fillable = ['name', 'ip_address' ,'port' ,'city','type'];
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
        $query = $query . substr($script->language,1) . ' /tmp/' .$script->_id . " run ".$parameters;
        $query = $query = "ssh -p " . $this->port . " " . $key->username . "@" . $this->ip_address . " -i ../keys/" .
            Auth::id() . " " . $query . " 2>&1";
        $output = shell_exec($query);
        return $output;
    }

    public function systemScript($name,$parameters){
        $key = Key::where([
            'server_id' => $this->id,
            'user_id' => Auth::id()
        ])->first();
        $name = $name . ".lmn";
        $copy_file_query = 'scp -P ' . $this->port . " -i ../keys/" . Auth::id() .' ' . storage_path('app/system_scripts/' . $name) .' ' . $key->username .'@' . $this->ip_address . ':/tmp/';
        shell_exec($copy_file_query);
        shell_exec('sudo chmod +x /tmp/' . $name);
        $query = 'sudo /usr/bin/env python3 /tmp/' .$name . " run ".$parameters;
        $query = $query = "ssh -p " . $this->port . " " . $key->username . "@" . $this->ip_address . " -i ../keys/" .
            Auth::id() . " " . $query . " 2>&1" . (($name == "network.lmn") ? " > /dev/null 2>/dev/null &" : "");
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

    public function sshPortEnabled(){
        $query = "echo exit | telnet " . $this->ip_address ." " . $this->port;
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
        // Trust server again just in case.
        shell_exec("ssh-keyscan -p " . $this->port . " -H ". $this->ip_address . " >> ~/.ssh/known_hosts");

        // Fix key file permissions again, just in case.
        $query = "chmod 400 ../keys/" . Auth::id();
        shell_exec($query);
        $query = "ssh -p " . $this->port . " " . $key->username . "@" . $this->ip_address . " -i ../keys/" .
            Auth::id() . " " . "whoami" . " 2>&1";

        $output = shell_exec($query);
        if($output != ($key->username . "\n")){
            return false;
        }else{
            return $key;
        }
    }

    public static function filterPermissions($raw_servers){
        // Ignore permissions if user is admin.
        if(\Auth::user()->isAdmin()){
            return $raw_servers;
        }

        // Get permissions from middleware.
        $permissions = request('permissions');

        // Create new array for permitted servers
        $servers = [];

        // Loop through each server and add permitted ones in servers array.
        foreach ($raw_servers as $server){
            if(in_array($server->_id, $permissions->server)){
                array_push($servers,$server);
            }
        }
        return $servers;
    }

    public static function getAll($coloumns = []){
        $servers = Server::all($coloumns);
        return Server::filterPermissions($servers);
    }
}