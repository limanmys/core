<?php

namespace App;

use App\Classes\Connector\Connector;
use App\Classes\Connector\SSHConnector;
use App\Classes\Connector\SNMPConnector;
use App\Classes\Connector\SSHCertificateConnector;
use App\Classes\Connector\WinRMConnector;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder;
use App\UserFavorites;

class Server extends Model
{
    use UsesUuid;

    /**
     * @var array
     */
    protected $fillable = [
        'name',
        'ip_address',
        'city',
        'type',
        'control_port',
    ];
    /**
     * @var
     */
    public $key;

    /**
     * @return Connector
     */
    private function connector()
    {
        if ($this->type == "linux_ssh") {
            return new SSHConnector($this, user()->id);
        } elseif ($this->type == "windows_powershell") {
            return new WinRMConnector($this, user()->id);
        } elseif ($this->type == "linux_certificate") {
            return new SSHCertificateConnector($this, user()->id);
        } elseif ($this->type == "snmp") {
            return new SNMPConnector($this,user()->id);
        }
        
        else {
            abort(
                504,
                "Bu sunucuda komut çalıştırmak için bir bağlantınız yok."
            );
        }
    }

    /**
     * @param $command
     * @param $log
     * @return string
     */
    public function run($command, $log = true)
    {
        if (!$this->canRunCommand()) {
            return respond("Bu sunucuda komut çalıştıramazsınız!",504);
        }

        // Execute and return outputs.
        return $this->connector()->execute($command, $log);
    }

    /**
     * @param $file
     * @param $path
     * @return bool
     * @throws \Throwable
     */
    public function putFile($file, $path)
    {
        return $this->connector()->sendFile($file, $path);
    }

    /**
     * @param $remote_path
     * @param $local_path
     * @return bool
     */
    public function getFile($remote_path, $local_path)
    {
        return $this->connector()->receiveFile($local_path, $remote_path);
    }

    /**
     * @param $script
     * @param $parameters
     * @param false $runAsRoot
     * @return string
     */
    public function runScript($script, $parameters, $runAsRoot = false)
    {
        // Create Connector Object
        $connector = $this->connector();

        return $connector->runScript($script, $parameters, $runAsRoot);
    }

    /**
     * @param $service_name
     * @return bool
     */
    public function isRunning($service_name)
    {
        if ($this->type == "windows" || $this->type == "linux") {
            if($this->control_port == -1){
                return true;
            }
            return is_resource(
                @fsockopen(
                    $this->ip_address,
                    $this->control_port,
                    $errno,
                    $errstr,
                    intval(config('liman.server_connection_timeout'))
                )
            );
        }
        // Check if services are alive or not.
        $query = sudo() . "systemctl is-failed " . $service_name;

        // Execute and return outputs.
        return $this->connector()->execute($query, false) == "active"
            ? true
            : false;
    }

    /**
     * @return bool
     */
    public function isAlive()
    {
        if($this->control_port == -1){
            return true;
        }
        // Simply Check Port If It's Alive
        if (
            is_resource(
                @fsockopen(
                    $this->ip_address,
                    $this->control_port,
                    $errno,
                    $errstr,
                    intval(config('liman.server_connection_timeout'))
                )
            )
        ) {
            return true;
        } else {
            // Abort, Since server is unavailable.
            abort(504, __("Sunucuya Bağlanılamadı."));
        }
        return false;
    }

    /**
     * @return Server|Server[]|Collection|Builder
     */
    public static function getAll()
    {
        return Server::get()->filter(function ($server) {
            return Permission::can(user()->id, 'server', 'id', $server->id);
        });
    }

    public function extensions()
    {
        return $this->belongsToMany('\App\Extension', 'server_extensions')
            ->get()
            ->filter(function ($extension) {
                return Permission::can(
                    user()->id,
                    'extension',
                    'id',
                    $extension->id
                );
            });
    }

    public function isFavorite()
    {
        return UserFavorites::where([
            "user_id" => user()->id,
            "server_id" => server()->id,
        ])->exists();
    }

    public function canRunCommand()
    {
        return $this->type == "linux_ssh" ||
            $this->type == "linux_certificate" ||
            $this->type == "windows_powershell" || 
            $this->type == "snmp";
    }

    public function isLinux()
    {
        return $this->type == "linux_ssh" ||
            $this->type == "linux_certificate" ||
            $this->type == "linux";
    }

    public function isWindows()
    {
        return $this->type == "windows" || $this->type == "windows_powershell";
    }

    public function getVersion()
    {
        if (!$this->canRunCommand() || $this->type == "snmp") {
            return "";
        }

        if ($this->isLinux()) {
            return $this->run("lsb_release -ds");
        }

        return explode(
            "|",
            $this->run("(Get-WmiObject Win32_OperatingSystem).name")[0]
        );
    }

    public function getHostname()
    {
        if (!$this->canRunCommand() || $this->type == "snmp") {
            return "";
        }

        return $this->run("hostname");
    }
}
