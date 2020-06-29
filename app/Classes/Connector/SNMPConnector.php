<?php

namespace App\Classes\Connector;

use App\UserSettings;

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
    
    public static $verifyCommands = [
        "iso.3.6.1.2.1.1.1.0"
    ];

    /**
     * SNMPConnector constructor.
     * @param \App\Server $server
     * @param null $user_id
     */
    public function __construct(\App\Server $server, $user_id)
    {
        $this->server = $server;
        $this->username = $this->getCredential("username");
        $this->securityLevel = $this->getCredential("SNMPsecurityLevel");
        $this->authProtocol = $this->getCredential("SNMPauthProtocol");
        $this->authPassword = $this->getCredential("SNMPauthPassword");
        $this->privacyProtocol = $this->getCredential("SNMPprivacyProtocol");
        $this->privacyPassword = $this->getCredential("SNMPprivacyPassword");
    }

    public function execute($command, $flag = true)
    {
        return snmp3_get($this->server->ip_address, $this->username, $this->securityLevel, $this->authProtocol, $this->authPassword, $this->privacyProtocol, $this->privacyPassword, $command);
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
     * @param \App\Server $server
     * @param $username
     * @param $password
     * @param $user_id
     * @param $key
     * @return bool
     */
    public static function create(
        \App\Server $server,
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

    public static function verifySnmp($ip_address, $username, $securityLevel, $authProtocol, $authPassword, $privacyProtocol, $privacyPassword ){
        foreach(SNMPConnector::$verifyCommands as $command){
            try{
                $flag = snmp3_get($ip_address, $username, $securityLevel, $authProtocol, $authPassword, $privacyProtocol, $privacyPassword, $command);
            }catch(\Exception $e){
                return respond($e->getMessage(),201);
            }
        }
        
        if(isset($flag)){
            return respond("SNMP bağlantısı doğrulandı.", 200);
        }
        return respond(
            "$username,$securityLevel,$authProtocol,$authPassword,$privacyProtocol,$privacyPassword,$ip_address",
            201
        );
        return isset($flag);
    }

    private function getCredential($name)
    {
        $object = UserSettings::where([
            'user_id' => user()->id,
            'server_id' => server()->id,
            'name' => $name,
        ])->first();
        if(!$object){
            abort(
                504,
                "Bu sunucu için SNMP anahtarınız yok. Kasa üzerinden bir anahtar ekleyebilirsiniz."
            );
        }
        return lDecrypt($object["value"]);
    }

}