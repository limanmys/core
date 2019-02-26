<?php

namespace App\Classes\Connector;

use App\Key;
use App\ServerLog;
use phpseclib\Crypt\RSA;
use phpseclib\Net\SFTP;
use phpseclib\Net\SSH2;

/**
 * Class SSHConnector
 * @package App\Classes
 */
class SSHConnector implements Connector
{
    /**
     * @var mixed
     */
    protected $connection;
    protected $server;
    protected $ssh;
    /**
     * SSHConnector constructor.
     * @param \App\Server $server
     * @throws \Throwable
     */
    public function __construct(\App\Server $server,$user_id = null)
    {
        ($key = Key::where([
            "user_id" => $user_id,
            "server_id" => $server->_id
        ])->first()) || abort(504,"SSH Anahtarınız yok.");
        $ssh = new SSH2($server->ip_address, $server->port);
        $rsa = new RSA();
        $rsa->password = env("APP_KEY") . $user_id;
        $rsa->loadKey(file_get_contents(storage_path('keys') . DIRECTORY_SEPARATOR . auth()->id()));
        if(!$ssh->login($key->username,$rsa)){
            abort(504,"Anahtarınız ile giriş yapılamadı.");
        }

        $this->ssh = $ssh;
        $this->key = $key;
        $this->server = $server;
    }

    /**
     * SSHConnector destructor
     */
    public function __destruct()
    {
        $this->ssh->disconnect();
    }

    /**
     * @param $command
     * @return string
     */
    public function execute($command)
    {
        ServerLog::new($command, $this->server->_id);
        return $this->ssh->exec($command);
    }

    /**
     * @param $localPath
     * @param $remotePath
     * @param int $permissions
     * @return bool
     */
    public function sendFile($localPath, $remotePath, $permissions = 0644)
    {
        $sftp = new SFTP($this->server->ip_address, $this->server->port);
        $key = new RSA();
        $key->password = env("APP_KEY") . auth()->id();
        $key->loadKey(file_get_contents(storage_path('keys') . DIRECTORY_SEPARATOR . auth()->id()));
        if(!$sftp->login("ubuntu",$key)){
            abort(504,"Anahtar Hatası");
        }
        return $sftp->put($remotePath, $localPath, SFTP::SOURCE_LOCAL_FILE);
    }

    /**
     * @param $localPath
     * @param $remotePath
     * @return bool
     */
    public function receiveFile($localPath, $remotePath)
    {
        $sftp = new SFTP($this->server->ip_address, $this->server->port);
        $key = new RSA();
        $key->password = env("APP_KEY") . auth()->id();
        $key->loadKey(file_get_contents(storage_path('keys') . DIRECTORY_SEPARATOR . auth()->id()));
        if(!$sftp->login("ubuntu",$key)){
            abort(504,"Anahtar Hatası");
        }
        return $sftp->get($remotePath, $localPath);
    }

    public static function create(\App\Server $server, $username, $password, $user_id)
    {
        if(!is_file(storage_path('keys') . DIRECTORY_SEPARATOR . auth()->id())){
            $rsa = new RSA();
            $rsa->password = env("APP_KEY") . $user_id;
            $rsa->comment = "liman";
            $rsa->setPublicKeyFormat(RSA::PUBLIC_FORMAT_OPENSSH);
            $keys = $rsa->createKey(4096);
            file_put_contents(storage_path('keys') . DIRECTORY_SEPARATOR . auth()->id(),$keys["privatekey"]);
            file_put_contents(storage_path('keys') . DIRECTORY_SEPARATOR . auth()->id() . ".pub",$keys["publickey"]);
        }else{
            $keys["publickey"] = file_get_contents(storage_path('keys') . DIRECTORY_SEPARATOR . auth()->id() . ".pub");
        }

        $ssh = new SSH2($server->ip_address, $server->port);
        $ssh->login($username,$password);
        $ssh->exec("echo '" . $keys["publickey"] . "' >> ~/.ssh/authorized_keys");
        $ssh->disconnect();
        return true;
    }
}