<?php

namespace App\Models;

use App\Connectors\GenericConnector;
use App\Support\Database\CacheQueryBuilder;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;

/**
 * Server Model
 *
 * @extends Model
 */
class Server extends Model
{
    use UsesUuid, CacheQueryBuilder;

    public $key;

    protected $fillable = [
        'name',
        'ip_address',
        'type',
        'control_port',
        'os',
        'user_id',
        'shared_key',
        'key_port',
        'enabled'
    ];

    /**
     * Get all servers
     *
     * @return Server| array | Collection | Builder
     */
    public static function getAll(): Server | array | Collection | Builder
    {
        return Server::get()->filter(function ($server) {
            return Permission::can(user()->id, 'server', 'id', $server->id);
        });
    }

    /**
     * Put file on server
     *
     * @param $file
     * @param $path
     * @return string|null
     *
     * @throws \Throwable
     */
    public function putFile($file, $path)
    {
        return $this->connector()->sendFile($file, $path);
    }

    /**
     * Create connector instance
     *
     * @return GenericConnector
     * @throws \Exception
     */
    private function connector()
    {
        if ($this->key() == null) {
            abort(
                504,
                'Bu sunucuda komut çalıştırmak için bir bağlantınız yok.'
            );
        }

        return new GenericConnector($this, user());
    }

    /**
     * Returns server key
     *
     * @return mixed
     */
    public function key()
    {
        if ($this->shared_key == 1) {
            return ServerKey::where('server_id', $this->id)->first();
        }

        return ServerKey::where([
            'server_id' => $this->id,
            'user_id' => user()->id,
        ])->first();
    }

    /**
     * Get file from server
     *
     * @param $remote_path
     * @param $local_path
     * @return string|null
     * @throws GuzzleException
     */
    public function getFile($remote_path, $local_path)
    {
        return $this->connector()->receiveFile($local_path, $remote_path);
    }

    /**
     * Check if service is running or not
     *
     * @param $service_name
     * @return bool
     * @throws GuzzleException
     */
    public function isRunning($service_name)
    {
        if (! $this->canRunCommand()) {
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
        $query = 'systemctl is-failed ' . $service_name;

        // Execute and return outputs.
        return $this->connector()->execute($query, false) == 'active'
            ? true
            : false;
    }

    /**
     * Determine if server can run command
     *
     * @return bool
     */
    public function canRunCommand()
    {
        return $this->key() != null ? true : false;
    }

    /**
     * Check if server is alive
     *
     * @return bool
     */
    public function isAlive(): bool
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
            abort(504, __('Sunucuya Bağlanılamadı.'));
        }

        return false;
    }

    public function isOnline(): bool
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
        }

        return false;
    }

    /**
     * Get extensions on server that in scope of permission system
     *
     * @return Collection
     */
    public function extensions()
    {
        return $this->belongsToMany(
            '\App\Models\Extension',
            'server_extensions'
        )
            ->orderBy("updated_at", "DESC")
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

    /**
     * Check if server is on favorite list
     *
     * @return mixed
     */
    public function isFavorite()
    {
        return UserFavorites::where([
            'user_id' => user()->id,
            'server_id' => server()->id,
        ])->exists();
    }

    /**
     * Returns true if server is Windows
     *
     * @return bool
     */
    public function isWindows()
    {
        return $this->os == 'windows';
    }

    /**
     * Get system version
     *
     * @return string
     * @throws GuzzleException
     */
    public function getVersion()
    {
        if (! $this->canRunCommand()) {
            return '';
        }

        if ($this->isLinux()) {
            return $this->run("cat /etc/os-release | grep ^PRETTY_NAME= | cut -d\"=\" -f2 | sed 's/\"//g'");
        }

        return explode(
            '|',
            $this->run('(Get-WmiObject Win32_OperatingSystem).name')
        )[0];
    }

    /**
     * Returns true if server is Linux
     *
     * @return bool
     */
    public function isLinux()
    {
        return $this->os == 'linux';
    }

    /**
     * Run command on server
     *
     * @param $command
     * @param $log
     * @return string
     * @throws GuzzleException
     */
    public function run($command, $log = true)
    {
        if (! $this->canRunCommand()) {
            return respond('Bu sunucuda komut çalıştıramazsınız!', 504);
        }

        // Execute and return outputs.
        return $this->connector()->execute($command, $log);
    }

    /**
     * Get server uptime
     *
     * @return string
     * @throws GuzzleException
     */
    public function getUptime()
    {
        if (! $this->canRunCommand()) {
            return '';
        }

        if ($this->isLinux()) {
            return $this->run('uptime -s');
        }

        return explode(
            '|',
            $this->run('wmic path Win32_OperatingSystem get LastBootUpTime')
        )[0];
    }

    /**
     * Get number of services
     *
     * @return string
     * @throws GuzzleException
     */
    public function getNoOfServices()
    {
        if (! $this->canRunCommand()) {
            return '';
        }

        $services = [];
        if ($this->isLinux()) {
            $raw = $this->run(
                "systemctl list-units --all | grep service | awk '{print $1 \":\"$2\" \"$3\" \"$4\":\"$5\" \"$6\" \"$7\" \"$8\" \"$9\" \"$10}'",
                false
            );
            foreach (explode("\n", $raw) as &$package) {
                if ($package == '') {
                    continue;
                }
                if (str_contains($package, '●')) {
                    $package = explode('●:', $package)[1];
                }
                $row = explode(':', trim($package));
                try {
                    if (str_contains($row[0], 'sysusers.service')) {
                        continue;
                    }

                    array_push($services, [
                        'name' => strlen($row[0]) > 50 ? substr($row[0], 0, 50) . '...' : $row[0],
                        'description' => strlen($row[2]) > 60 ? substr($row[2], 0, 60) . '...' : $row[2],
                        'status' => $row[1],
                    ]);
                } catch (\Exception) {
                }
            }

            $raw = $this->run(
                "systemctl list-unit-files --state=disabled | grep service | awk '{print $1 \":\"$2}'",
                false
            );

            foreach (explode("\n", $raw) as &$package) {
                if ($package == '') {
                    continue;
                }
                $row = explode(':', trim($package));
                $services[] = [
                    'name' => strlen($row[0]) > 50 ? substr($row[0], 0, 50) . '...' : $row[0],
                    'status' => $row[1] == 'disabled',
                ];
            }

            return count($services);
        }

        return $this->run('(Get-Service | Measure-Object).Count');
    }

    /**
     * Get number of running processes
     *
     * @return string
     * @throws GuzzleException
     */
    public function getNoOfProcesses()
    {
        if (! $this->canRunCommand()) {
            return '';
        }

        if ($this->isLinux()) {
            return $this->run('ps -aux | wc -l');
        }

        return explode(
            '|',
            $this->run('(Get-Process).Count')
        )[0];
    }

    /**
     * Get server hostname
     *
     * @return string
     * @throws GuzzleException
     */
    public function getHostname()
    {
        if (! $this->canRunCommand()) {
            return '';
        }

        return $this->run('hostname');
    }
}
