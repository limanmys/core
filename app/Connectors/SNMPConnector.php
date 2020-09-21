<?php

namespace App\Connectors;

use App\Models\UserSettings;

/**
 * Class SNMPConnector
 * @package App\Classes
 */
class SNMPConnector implements Connector
{
    /**
     * @var mixed
     */
    protected $connection;
    protected $server;
    protected $ssh;
    protected $key;
    protected $user_id;
    protected $username;
    protected $securityLevel;
    protected $authProtocol;
    protected $authPassword;
    protected $privacyProtocol;
    protected $privacyPassword;

    public static $verifyCommands = ["iso.3.6.1.2.1.1.1.0"];

    /**
     * SNMPConnector constructor.
     * @param \App\Models\Server $server
     * @param null $user_id
     */
    public function __construct(\App\Models\Server $server, $user_id)
    {
        list($username, $password, $port) = self::retrieveCredentials();
        $this->server = $server;
        $this->username = $username;
        $this->securityLevel = 'authPriv';
        $this->authProtocol = 'SHA';
        $this->authPassword = $password;
        $this->privacyProtocol = 'AES';
        $this->privacyPassword = $password;
    }

    public function execute($command, $flag = true)
    {
        return snmp3_get(
            $this->server->ip_address,
            $this->username,
            $this->securityLevel,
            $this->authProtocol,
            $this->authPassword,
            $this->privacyProtocol,
            $this->privacyPassword,
            $command
        );
    }

    /**
     * @param $script
     * @param $parameters
     * @param null $extra
     * @return string
     */
    public function runScript($script, $parameters, $runAsRoot = false)
    {
    }

    public function sendFile($localPath, $remotePath, $permissions = 0644)
    {
    }

    public function receiveFile($localPath, $remotePath)
    {
    }

    /**
     * @param \App\Models\Server $server
     * @param $username
     * @param $password
     * @param $user_id
     * @param $key
     * @return bool
     */
    public static function create(
        \App\Models\Server $server,
        $username,
        $password,
        $user_id,
        $key,
        $port = null
    ) {
    }

    public static function verify($ip_address, $username, $password, $port)
    {
    }

    public static function createSnmp()
    {
        return true;
    }

    public static function verifySnmp($ip_address, $username, $authPassword)
    {
        foreach (SNMPConnector::$verifyCommands as $command) {
            try {
                $flag = snmp3_get(
                    $ip_address,
                    $username,
                    'authPriv',
                    'SHA',
                    $authPassword,
                    'DES',
                    $authPassword,
                    $command
                );
            } catch (\Exception $e) {
                return respond($e->getMessage(), 201);
            }
        }

        if (isset($flag)) {
            return "ok";
        }
        return "nok";
    }

    public static function retrieveCredentials()
    {
        if (server()->key() == null) {
            abort(
                504,
                "Bu sunucu için SNMP anahtarınız yok. Kasa üzerinden bir anahtar ekleyebilirsiniz."
            );
        }
        $data = json_decode(server()->key()->data, true);

        return [
            lDecrypt($data["clientUsername"]),
            lDecrypt($data["clientPassword"]),
            array_key_exists("key_port", $data)
                ? intval($data["key_port"])
                : 161,
        ];
    }
}
