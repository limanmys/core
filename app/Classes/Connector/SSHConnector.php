<?php

namespace App\Classes\Connector;

use phpseclib\Crypt\RSA;
use phpseclib\Net\SFTP;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use App\UserSettings;

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
    protected $key;
    protected $user_id;
    protected $username;

    /**
     * SSHConnector constructor.
     * @param \App\Server $server
     * @param null $user_id
     */
    public function __construct(\App\Server $server, $user_id)
    {
        $ip_address = str_replace(".", "_", $server->ip_address);
        if (!session($ip_address)) {
            list($username, $password) = self::retrieveCredentials();
            self::init($username, $password, $server->ip_address);
        }

        return true;
    }

    /**
     * SSHConnector destructor
     */
    public function __destruct()
    {
    }


    public function execute($command,$flag = true)
    {
        // Make IP Session Safe
        $ip_address = str_replace(".", "_", server()->ip_address);
        return self::request('run',[
            "token" => session($ip_address),
            "command" => $command
        ]);
    }

    /**
     * @param $script
     * @param $parameters
     * @param null $extra
     * @return string
     */
    public function runScript($script, $parameters, $extra = null)
    {
        $scriptsPath = env('EXTENSIONS_PATH') . $script->extensions
            . DIRECTORY_SEPARATOR . "scripts" . DIRECTORY_SEPARATOR . $script->unique_code . ".lmns";
        if(!is_file($scriptsPath)){
            abort("Betik bulunamadı" . $script->extensions
                . DIRECTORY_SEPARATOR . "scripts" . DIRECTORY_SEPARATOR . $script->unique_code . ".lmns");
        }
        $remotePath = '/tmp/' . $script->id;
        $this->sendFile($scriptsPath, $remotePath,0555);

        $localHash = md5_file($scriptsPath);
        $remoteHash = explode(' ',substr($this->execute('md5sum ' . $remotePath,false),0 , -1))[0];
        
        if($localHash != $remoteHash){
            abort(504,"Betik gönderilemedi, internet kesintisi oluşmuş veya disk dolmuş olabilir.");
        }

        // First Let's Run Before Part Of the Script
        $query = ($script->root == 1) ? 'sudo ' : '';
        $query = $query . $script->language . ' /tmp/' . $script->id . " before " . $parameters . $extra;
        $before = $this->execute($query,false);
        if($before != "ok\n"){
//            ServerLog::new($query,$before, $this->server->id,$this->user_id);
            abort(504, $before);
        }

        // Run Part Of The Script
        $query = ($script->root == 1) ? 'sudo ' : '';
        $query = $query . $script->language . ' /tmp/' . $script->id . " run " . $parameters . $extra;
        $output = $this->execute($query);

        // Run After Part Of the Script
        $query = ($script->root == 1) ? 'sudo ' : '';
        $query = $query . $script->language . ' /tmp/' . $script->id . " after " . $parameters . $extra;
        $after = $this->execute($query,false);
        if($after != "ok\n"){
//            ServerLog::new($query,$after, $this->server->id,$this->user_id);
            abort(504, $after);
        }

        return $output;
    }

    public function sendFile($localPath, $remotePath, $permissions = 0644)
    {
        // Make IP Session Safe
        $ip_address = str_replace(".", "_", server()->ip_address);
        return self::request('send',[
            "token" => session($ip_address),
            "local_path" => $localPath,
            "remote_path" => $remotePath
        ]);
    }

    public static function verify($ip_address, $username, $password,$port)
    {
        $token = self::init($username, $password, $ip_address);
        if ($token) {
            return respond("Kullanıcı adı ve şifre doğrulandı.", 200);
        }
        return respond("Bu Kullanıcı adı ve şifre ile bağlanılamadı.", 201);
    }

    public function receiveFile($localPath, $remotePath)
    {
        // Make IP Session Safe
        $ip_address = str_replace(".", "_", server()->ip_address);
        return self::request('get',[
            "token" => session($ip_address),
            "local_path" => $localPath,
            "remote_path" => $remotePath
        ]);
    }

    /**
     * @param \App\Server $server
     * @param $username
     * @param $password
     * @param $user_id
     * @param $key
     * @return bool
     */
    public static function create(\App\Server $server, $username, $password, $user_id,$key)
    {
        $token = self::init($username, $password, $server->ip_address);
        if ($token) {
            return "OK";
        } else {
            return "NO";
        }
    }

    public static function retrieveCredentials()
    {
        $username = UserSettings::where([
            'user_id' => user()->id,
            'server_id' => server()->id,
            'name' => 'clientUsername'
        ])->first();
        $password = UserSettings::where([
            'user_id' => user()->id,
            'server_id' => server()->id,
            'name' => 'clientPassword'
        ])->first();

        if (!$username || !$password) {
            abort(504, "Bu sunucu için SSH anahtarınız yok. Kasa üzerinden bir anahtar ekleyebilirsiniz.");
        }

        $key = env('APP_KEY') . user()->id . server()->id;
        $decrypted = openssl_decrypt($username["value"], 'aes-256-cfb8', $key);
        $stringToDecode = substr($decrypted, 16);
        $username = base64_decode($stringToDecode);

        $key = env('APP_KEY') . user()->id . server()->id;
        $decrypted = openssl_decrypt($password["value"], 'aes-256-cfb8', $key);
        $stringToDecode = substr($decrypted, 16);
        $password = base64_decode($stringToDecode);
        return [$username, $password];
    }

    public static function request($url, $params,$retry = 3)
    { 
        // First, format ip adress.
        $ip_address = str_replace(".", "_", server()->ip_address);
        // If Session doesn't have token, create one.
        if (!session($ip_address)) {
            // Retrieve Credentials
            list($username, $password) = self::retrieveCredentials();

            // Execute Init
            self::init($username, $password, server()->ip_address);
        }
        // Create Guzzle Object.
        $client = new Client();
        // Make Request.
        try{
            $params["token"] = session($ip_address);
            $res = $client->request('POST', env("LIMAN_CONNECTOR_SERVER"). '/' . $url, ["form_params" => $params]);
        }catch(BadResponseException $e){
            // In case of error, handle error.
            $json = json_decode((string) $e->getResponse()->getBody()->getContents());

            // If it's first time, retry after recreating ticket.
            if($retry){
                list($username, $password) = self::retrieveCredentials();
                self::init($username, $password, server()->ip_address);
                self::request($url,$params,$retry -1 );
                return;
            }else{
                // If nothing works, abort.
                abort(403,"Anahtarınız ile sunucuya giriş yapılamadı.(" . $json->error . ")");
            }
        }
        
        // Simply parse and return output.
        $json = json_decode((string) $res->getBody());
        return $json->output;
    }

    public static function init($username, $password, $hostname)
    {
        $client = new Client();
        $res = $client->request('POST', env('LIMAN_CONNECTOR_SERVER') . '/new', [
            'form_params' => [
                "username" => $username,
                "password" => $password,
                "hostname" => $hostname,
                "connection_type" => "ssh"
            ],
            'timeout' => 5
        ]);
        $json = json_decode((string) $res->getBody());
        //Escape For . character in session.
        $hostname = str_replace(".", "_", $hostname);
        if (auth() && auth()->user()) {
            session()->put([
                $hostname => $json->token
            ]);
        }
        return $json->token;
    }
}