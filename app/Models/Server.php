<?php

namespace App\Models;

use App\Connectors\GenericConnector;
use App\Connectors\SNMPConnector;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder;
use App\Models\UserFavorites;

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
        'os',
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
        if ($this->key() == null) {
            abort(
                504,
                "Bu sunucuda komut çalıştırmak için bir bağlantınız yok."
            );
        }
        if ($this->key()->type == "snmp") {
            return new SNMPConnector($this, user()->id);
        }
        return new GenericConnector($this, user());
    }

    /**
     * @param $command
     * @param $log
     * @return string
     */
    public function run($command, $log = true)
    {
        if (!$this->canRunCommand()) {
            return respond("Bu sunucuda komut çalıştıramazsınız!", 504);
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
        if (!$this->canRunCommand()) {
            if ($this->control_port == -1) {
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
        $query = "systemctl is-failed " . $service_name;

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
        if ($this->control_port == -1) {
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
        return $this->belongsToMany(
            '\App\Models\Extension',
            'server_extensions'
        )
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
        return $this->key() != null ? true : false;
    }

    public function isLinux()
    {
        return $this->os == "linux";
    }

    public function isWindows()
    {
        return $this->os == "windows";
    }

    public function getVersion()
    {
        if (!$this->canRunCommand()) {
            return "";
        }

        if ($this->isLinux()) {
            return $this->run("lsb_release -ds");
        }
        return explode(
            "|",
            $this->run("(Get-WmiObject Win32_OperatingSystem).name")
        )[0];
    }

    public function getUptime()
    {
        if (!$this->canRunCommand()) {
            return "";
        }

        if ($this->isLinux()) {
            return $this->run("uptime -s");
        }
        return explode(
            "|",
            $this->run("wmic path Win32_OperatingSystem get LastBootUpTime")
        )[0];
    }

    public function getNoOfServices()
    {
        if (!$this->canRunCommand()) {
            return "";
        }

        if ($this->isLinux()) {
            return $this->run("systemctl list-units --type=service --state=active | wc -l");
        }
        return explode(
            "|",
            $this->run("wmic service get name | find \"\" /v /c")
        )[0];
    }

    public function getNoOfProcesses()
    {
        if (!$this->canRunCommand()) {
            return "";
        }

        if ($this->isLinux()) {
            return $this->run("ps -aux | wc -l");
        }
        return explode(
            "|",
            $this->run("(Get-Process).Count")
        )[0];
    }

    public function getHostname()
    {
        if (!$this->canRunCommand()) {
            return "";
        }

        return $this->run("hostname");
    }

    public function key()
    {
        return ServerKey::where([
            "server_id" => $this->id,
            "user_id" => user()->id
        ])->first();
    }
}
