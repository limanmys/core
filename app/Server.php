<?php

namespace App;

use App\Classes\Connector\SSHConnector;
use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

/**
 * Class Server
 * @package App
 * @method static \Illuminate\Database\Query\Builder|\Server where($field, $value)
 * @method static \Illuminate\Database\Query\Builder|\Server find($field)
 */
class Server extends Eloquent
{
    /**
     * @var string
     */
    protected $collection = 'servers';
    /**
     * @var string
     */
    protected $connection = 'mongodb';
    /**
     * @var array
     */
    protected $fillable = ['name', 'ip_address', 'city', 'type', 'control_port'];
    /**
     * @var
     */
    public $key;

    /**
     * @return SSHConnector
     * @throws Exceptions\Key\NotFound
     * @throws \Throwable
     */
    private function connector()
    {
        if($this->type == "linux_ssh"){
            return new SSHConnector($this,auth()->id());
        }
    }

    /**
     * @param $command
     * @return string
     * @throws Exceptions\Key\NotFound
     * @throws \Throwable
     */
    public function run($command)
    {
        // Execute and return outputs.
        return $this->connector()->execute($command);
    }

    /**
     * @param $file
     * @param $path
     * @return bool
     * @throws Exceptions\Key\NotFound
     * @throws \Throwable
     */
    public function putFile($file, $path)
    {
        return $this->connector()->sendFile($file,$path);
    }

    /**
     * @param $remote_path
     * @param $local_path
     * @return bool
     * @throws Exceptions\Key\NotFound
     * @throws \Throwable
     */
    public function getFile($remote_path, $local_path)
    {
        return $this->connector()->receiveFile($local_path, $remote_path);
    }

    /**
     * @param $script
     * @param $parameters
     * @param null $extra
     * @return string
     * @throws Exceptions\Key\NotFound
     * @throws \Throwable
     */
    public function runScript($script, $parameters, $extra = null)
    {
        // Create Connector Object
        $connector = $this->connector();

        return $connector->runScript($script,$parameters,$extra);
    }

    /**
     * @param $service_name
     * @return string
     * @throws Exceptions\Key\NotFound
     * @throws \Throwable
     */
    public function isRunning($service_name)
    {
        if($this->type == "windows" || $this->type == "linux"){
            return is_resource(@fsockopen($this->ip_address,$this->control_port,$errno, $errstr,env('SERVER_CONNECTION_TIMEOUT')));
        }
        // Check if services are alive or not.
        $query = "sudo systemctl is-failed " . $service_name;

        // Execute and return outputs.
        return ($this->connector()->execute($query) == "active\n") ? true : false;
    }

    /**
     * @return bool
     */
    public function isAlive()
    {
        // Simply Check Port If It's Alive
        if(is_resource(@fsockopen($this->ip_address,$this->control_port,$errno, $errstr,env('SERVER_CONNECTION_TIMEOUT')))){
            return true;
        }else{
            // Abort, Since server is unavailable.
            abort(504, __("Sunucuya Bağlanılamadı."));
        }
        return false;
    }

    /**
     * @return Server|\Illuminate\Database\Query\Builder
     */
    public static function getAll()
    {
        return Server::find(Permission::get(auth()->id(), 'server'));
    }

}