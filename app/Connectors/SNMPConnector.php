<?php

namespace App\Connectors;

/**
 * Class SNMPConnector
 */
class SNMPConnector implements Connector
{
    /**
     * @var mixed
     */
    protected $connection;

    protected $ssh;

    protected $key;

    protected $user_id;

    protected $username;

    protected $securityLevel = 'authPriv';

    protected $authProtocol = 'SHA';

    protected $authPassword;

    protected $privacyProtocol = 'AES';

    protected $privacyPassword;

    public static $verifyCommands = ['iso.3.6.1.2.1.1.1.0'];

    /**
     * SNMPConnector constructor.
     *
     * @param  \App\Models\Server  $server
     * @param  null  $user_id
     */
    public function __construct(protected \App\Models\Server $server, $user_id)
    {
        [$username, $password, $port] = self::retrieveCredentials();
        $this->username = $username;
        $this->authPassword = $password;
        $this->privacyPassword = $password;
    }

    public function execute($command, $flag = true): string|bool
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

    public function sendFile($localPath, $remotePath, $permissions = 0644)
    {
    }

    public function receiveFile($localPath, $remotePath)
    {
    }

    /**
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

    public static function createSnmp(): bool
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
            return 'ok';
        }

        return 'nok';
    }

    public static function retrieveCredentials()
    {
        if (server()->key() == null) {
            abort(
                504,
                'Bu sunucu için SNMP anahtarınız yok. Kasa üzerinden bir anahtar ekleyebilirsiniz.'
            );
        }
        $data = json_decode((string) server()->key()->data, true);

        return [
            lDecrypt($data['clientUsername']),
            lDecrypt($data['clientPassword']),
            array_key_exists('key_port', $data)
                ? intval($data['key_port'])
                : 161,
        ];
    }
}
