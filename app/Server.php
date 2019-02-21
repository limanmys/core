<?php

namespace App;

use Auth;
use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

/**
 * App\Server
 *
 * @property-read mixed $id
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Server newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Server newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Server query()
 * @method static \Illuminate\Database\Query\Builder|\App\Server where($field, $value)
 * @mixin \Eloquent
 */

class Server extends Eloquent
{
    protected $collection = 'servers';
    protected $connection = 'mongodb';
    protected $fillable = ['name', 'ip_address', 'city', 'type', 'control_port'];
    public $key;

    public function run($command)
    {
        // Execute and return outputs.
        return $this->runSSH($command);
    }

    public function runSSH($query, $extra = null)
    {
        ServerLog::new($query . $extra);
        // Build Query
        $query = "ssh -p " . $this->port . " " . $this->key->username . "@" . $this->ip_address . " -i " . storage_path('keys') .
            DIRECTORY_SEPARATOR . Auth::id() . " " . $query . " 2>&1" . $extra;
        // Execute and return outputs.
        return shell_exec($query);
    }

    public function putFile($file,$path)
    {
        // First, copy file through scp.
        $query = 'scp -P ' . $this->port . " -i " . storage_path('keys') . DIRECTORY_SEPARATOR . Auth::id() .
            ' ' . $file . ' ' .
            $this->key->username . '@' . $this->ip_address . ':' . $path;

        // Execute and return outputs.
        return shell_exec($query);
    }

    public function getFile($remote_path,$local_path){
        // First, retrieve file through scp.
        $query = 'scp -P ' . $this->port . " -i " . storage_path('keys') . DIRECTORY_SEPARATOR . Auth::id() .
            ' ' . $this->key->username . '@' . $this->ip_address . ':' . $remote_path . ' ' .
            $local_path;

        // Execute and return outputs.
        return shell_exec($query);
    }

    public function runScript($script, $parameters,$extra = null){

        // Copy script to target.
        $this->putFile(storage_path('app/scripts/' . $script->_id), '/tmp/');

        // Build Query
        $query = ($script->root == 1) ? 'sudo ' : '';
        $query = $query . $script->language . ' /tmp/' . $script->_id . " run " . $parameters;

        // Execute and return outputs.
        return $this->runSSH($query,$extra);
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

    public function sshAccessEnabled()
    {
        $key = $this->sshKey();
        if (!$this->isAlive() || !$key) {
            return false;
        }
        return true;
    }

    public function sshKey()
    {
        // Retrieve Key information from database.
        $key = Key::where([
            'server_id' => $this->id,
            'user_id' => Auth::id()
        ])->first();

        // If there's no key, abort.
        if (!$key) {
            return false;
        }

        // Add key object to the model as a variable to access it later.
        $this->key = $key;

        //Check if server is already trusted or not.
        if(shell_exec("ssh-keygen -F " . $this->ip_address . " 2>/dev/null") == null){

            // Trust Target Server
            shell_exec("ssh-keyscan -p " . $this->port . " -H ". $this->ip_address . " >> ~/.ssh/known_hosts");
        }

        // Fix key file permissions again, just in case.
        $query = "chmod 400 " . storage_path('keys')  . DIRECTORY_SEPARATOR . Auth::id();
        shell_exec($query);

        // Run whoami on target so we can verify key.
        $output = $this->runSSH("whoami");

        if ($output != ($key->username . "\n")) {
            return false;
        }

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

    /**
     * @param array $coloumns
     * @return \App\Server | Jenssegers\Mongodb\Eloquent\Model
     */
    public static function getAll($coloumns = [])
    {
        $servers = Server::all($coloumns);
        return Server::filterPermissions($servers);
    }

}