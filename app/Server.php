<?php

namespace App;

use Auth;
use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class Server extends Eloquent
{
    protected $collection = 'servers';
    protected $connection = 'mongodb';
    protected $fillable = ['name', 'ip_address', 'port', 'city', 'type', 'control_port'];
    public $key;

    public function run($command)
    {
        // Execute and return outputs.
        return $this->runSSH($command);
    }

    private function runSSH($query)
    {
        // Log Query
        server_log($this->_id, "command_" . $query);
        // Build Query
        $query = "ssh -p " . $this->port . " " . $this->key->username . "@" . $this->ip_address . " -i " . storage_path('keys') .
            DIRECTORY_SEPARATOR . Auth::id() . " " . $query . " 2>&1";
        echo $query;
        // Execute and return outputs.
        return shell_exec($query);
    }

    private function putFile($script)
    {
        // First, copy file through scp.
        $query = 'scp -P ' . $this->port . " -i " . storage_path('keys') . DIRECTORY_SEPARATOR . Auth::id() .
            ' ' . storage_path('app/scripts/' . $script->_id) . ' ' .
            $this->key->username . '@' . $this->ip_address . ':/tmp/';

        // Execute and return outputs.
        return shell_exec($query);
    }

    public function runScript($script, $parameters)
    {
        // Copy script to target.
        $this->putFile($script);

        // Build Query
        $query = ($script->root == 1) ? 'sudo ' : '';
        $query = $query . substr($script->language, 1) . ' /tmp/' . $script->_id . " run " . $parameters;

        // Execute and return outputs.
        return $this->runSSH($query);
    }

    public function systemScript($name, $parameters)
    {
        $name = $name . ".lmn";
        $copy_file_query = 'scp -P ' . $this->port . " -i " . storage_path('keys') . DIRECTORY_SEPARATOR . Auth::id() . ' ' . storage_path('app/system_scripts/' . $name) . ' ' . $this->key->username . '@' . $this->ip_address . ':/tmp/';
        shell_exec($copy_file_query);
        shell_exec('sudo chmod +x /tmp/' . $name);
        $query = 'sudo /usr/bin/env python3 /tmp/' . $name . " run " . $parameters;
        $query = $query = "ssh -p " . $this->port . " " . $this->key->username . "@" . $this->ip_address . " -i " .
            storage_path('keys') . DIRECTORY_SEPARATOR . Auth::id() . " " . $query . " 2>&1" . (($name == "network.lmn") ? " > /dev/null 2>/dev/null &" : "");
        return shell_exec($query);
    }

    public function isRunning($service_name)
    {
        // Check if services are alive or not.
        $query = "sudo systemctl is-failed " . $service_name;

        // Execute and return outputs.
        return $this->runSSH($query);
    }

    public function integrity()
    {
        if ($this->type == "linux_ssh") {
            return $this->sshAccessEnabled();
        }
        return true;
    }

    public function isAlive()
    {
        // Use telnet to check if server alive.
        $output = shell_exec("echo exit | telnet " . $this->ip_address . " " . $this->control_port);

        return strpos($output, "Connected to " . $this->ip_address);
    }

    private function sshAccessEnabled()
    {
        $key = $this->sshKey();
        if (!$this->isAlive() || !$key) {
            return false;
        }
        return true;
    }

    private function sshKey()
    {
        $key = Key::where([
            'server_id' => $this->id,
            'user_id' => Auth::id()
        ])->first();
        if ($key == null) {
            return false;
        }
        // Trust server again just in case.
        shell_exec("ssh-keyscan -p " . $this->port . " -H " . $this->ip_address . " >> ~/.ssh/known_hosts");

        // Fix key file permissions again, just in case.
        $query = "chmod 400 " . storage_path('keys')  . DIRECTORY_SEPARATOR . Auth::id();
        shell_exec($query);
        $query = "ssh -p " . $this->port . " " . $key->username . "@" . $this->ip_address . " -i " . storage_path('keys') .
            DIRECTORY_SEPARATOR . Auth::id() . " " . "whoami" . " 2>&1";

        $output = shell_exec($query);
        if ($output != ($key->username . "\n")) {
            return false;
        }
        $this->key = $key;
        return true;
    }

    public static function filterPermissions($raw_servers)
    {
        // Ignore permissions if user is admin.
        if (\Auth::user()->isAdmin()) {
            return $raw_servers;
        }

        // Get permissions from middleware.
        $permissions = request('permissions');

        // Create new array for permitted servers
        $servers = [];

        // Loop through each server and add permitted ones in servers array.
        foreach ($raw_servers as $server) {
            if (in_array($server->_id, $permissions->server)) {
                array_push($servers, $server);
            }
        }
        return $servers;
    }

    public static function getAll($coloumns = [])
    {
        $servers = Server::all($coloumns);
        return Server::filterPermissions($servers);
    }
}