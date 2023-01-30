<?php

namespace App\Connectors;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * SNMP Connector
 */
class SNMPConnector implements Connector
{
    public static $verifyCommands = ['iso.3.6.1.2.1.1.1.0'];

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

    /**
     * SNMPConnector constructor.
     *
     * @param \App\Models\Server $server
     * @param string $user_id
     * @throws \Exception
     * @throws \Exception
     */
    public function __construct(protected \App\Models\Server $server, $user_id)
    {
        [$username, $password, $port] = self::retrieveCredentials();
        $this->username = $username;
        $this->authPassword = $password;
        $this->privacyPassword = $password;
    }

    /**
     * Retrieves credential for server
     *
     * @return array
     * @throws \Exception
     * @throws \Exception
     */
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

    /**
     * @return void
     */
    public static function create(
        \App\Models\Server $server,
                           $username,
                           $password,
                           $user_id,
                           $key,
                           $port = null
    )
    {
    }

    /**
     * @param $ip_address
     * @param $username
     * @param $password
     * @param $port
     * @return void
     */
    public static function verify($ip_address, $username, $password, $port)
    {
    }

    /**
     * @return true
     */
    public static function createSnmp(): bool
    {
        return true;
    }

    /**
     * Verify SNMP connection is valid
     *
     * @param $ip_address
     * @param $username
     * @param $authPassword
     * @return JsonResponse|Response|string
     */
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

    /**
     * Execute SNMP command
     *
     * @param $command
     * @param $flag
     * @return string|bool
     */
    public function execute($command, $flag = true): string | bool
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
     * @param $localPath
     * @param $remotePath
     * @param $permissions
     * @return void
     */
    public function sendFile($localPath, $remotePath, $permissions = 0644)
    {
    }

    /**
     * @param $localPath
     * @param $remotePath
     * @return void
     */
    public function receiveFile($localPath, $remotePath)
    {
    }
}
