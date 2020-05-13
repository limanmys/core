<?php

namespace App\Classes\Connector;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use App\UserSettings;
use Illuminate\Support\Str;
use App\ConnectorToken;
use phpseclib\Net\SSH2;
use phpseclib\Crypt\RSA;
use phpseclib\Net\SFTP;

/**
 * Class SSHCertificateConnector
 * @package App\Classes
 */
class SSHCertificateConnector implements Connector
{
    /**
     * @var mixed
     */
    private $shell = null;
    private $sftp = null;
    /**
     * SSHCertificateConnector constructor.
     * @param \App\Server $server
     * @param null $user_id
     */
    public function __construct(\App\Server $server, $user_id)
    {
        list($username, $password) = self::retrieveCredentials();
        self::init($username, $password, $server->ip_address,$server->key_port ? $server->key_port : 22);
        return true;
    }

    public function execute($command, $flag = true)
    {
        return trim($this->shell->exec($command));
    }

    /**
     * @param $script
     * @param $parameters
     * @param null $extra
     * @return string
     */
    public function runScript($script, $parameters, $runAsRoot = false)
    {
        $remotePath = "/tmp/" . Str::random();

        $this->sendFile($script, $remotePath);
        $output = $this->execute("[ -f '$remotePath' ] && echo 1 || echo 0");
        if ($output != "1") {
            abort(504, "Betik gönderilemedi");
        }
        $this->execute("chmod +x " . $remotePath);

        // Run Part Of The Script
        $query = $runAsRoot ? sudo() : '';
        $query = $query . $remotePath . " " . $parameters . " 2>&1";
        $output = $this->execute($query);

        return $output;
    }

    public function sendFile($localPath, $remotePath, $permissions = 0644)
    {
        if ($this->sftp == null) {
            $sftp = new SFTP(server()->ip_address,server()->key_port);
            list($username, $password) = self::retrieveCredentials();
            if (!$sftp->login($username, $password)) {
                return false;
            }

            $this->sftp = $sftp;
        }
        return $this->sftp->put(
            $remotePath,
            $localPath,
            SFTP::SOURCE_LOCAL_FILE
        );
    }

    public static function verify($ip_address, $username, $password, $port)
    {
        $ssh = new SSH2($ip_address, $port);
        $key = new RSA();
        $key->loadKey($password);
        if (!$ssh->login($username, $key)) {
            return respond(
                "Bu Kullanıcı adı ve anahtar ile bağlanılamadı.",
                201
            );
        }

        return respond("Kullanıcı adı ve anahtar doğrulandı.", 200);
    }

    public function receiveFile($localPath, $remotePath)
    {
        if ($this->sftp == null) {
            $sftp = new SFTP(server()->ip_address);
            list($username, $password) = self::retrieveCredentials();
            if (!$sftp->login($username, $password)) {
                return false;
            }

            $this->sftp = $sftp;
        }

        return $this->sftp->get($remotePath, $localPath);
    }

    public static function create(
        \App\Server $server,
        $username,
        $password,
        $user_id,
        $key,
        $port = null
    ) {
        return true;
    }

    public function retrieveCredentials()
    {
        $username = UserSettings::where([
            'user_id' => user()->id,
            'server_id' => server()->id,
            'name' => 'clientUsername',
        ])->first();
        $password = UserSettings::where([
            'user_id' => user()->id,
            'server_id' => server()->id,
            'name' => 'clientPassword',
        ])->first();

        if (!$username || !$password) {
            abort(
                504,
                "Bu sunucu için SSH anahtarınız yok. Kasa üzerinden bir anahtar ekleyebilirsiniz."
            );
        }

        return [lDecrypt($username["value"]), lDecrypt($password["value"])];
    }

    public function init($username, $password, $hostname, $port = 22, $putSession = true)
    {
        $ssh = new SSH2($hostname,$port);
        $key = new RSA();
        $key->loadKey($password);
        if (!$ssh->login($username, $key)) {
            return false;
        }
        $this->shell = $ssh;

        return true;
    }
}
