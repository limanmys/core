<?php

namespace App\Classes\Connector;

use App\Server;
use App\UserSettings;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use Illuminate\Support\Str;

class WinRMConnector implements Connector
{
    public function __construct(Server $server, $user_id)
    {
        $ip_address = str_replace(".", "_", $server->ip_address);
        if (!session($ip_address)) {
            list($username, $password) = self::retrieveCredentials();
            self::init($username, $password, $server->ip_address);
        }
        return true;
    }

    public function __destruct()
    { }

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
            abort(504, "Bu sunucu için WinRM anahtarınız yok. Kasa üzerinden bir anahtar ekleyebilirsiniz.");
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

    public function execute($command)
    {
        // Make IP Session Safe
        $ip_address = str_replace(".", "_", server()->ip_address);
        // Prepare Powershell Command
        $command = "powershell.exe -encodedCommand " . base64_encode(mb_convert_encoding("\$ProgressPreference = \"SilentlyContinue\"; " . $command,"UTF-16LE","UTF-8"));
        return self::request('run',[
            "token" => session($ip_address),
            "command" => $command
        ]);
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
                return self::request($url,$params,$retry -1 );
            }else{
                // If nothing works, abort.
                abort(403,"Anahtarınız ile sunucuya giriş yapılamadı.");
            }
        }
        
        // Simply parse and return output.
        $json = json_decode((string) $res->getBody());
        return $json->output;
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

    public function runScript($script, $parameters, $runAsRoot)
    {
        // Find Remote Path
        $remotePath = "\\Windows\\Temp\\". Str::random() . ".ps1";

        $flag = $this->sendFile($script, $remotePath);

        // Find Out Letter. DISABLED FOR NOW
        // $letter = $this->execute("\$pwd.drive.name");

        // $letter = substr($letter,0, -2);

        $query = "C:\\" . $remotePath . " " . $parameters;

        $output = $this->execute($query);

        return $output;
        
    }

    public static function verify($ip_address, $username, $password, $port)
    {
        $token = self::init($username, $password, $ip_address);
        if ($token) {
            return respond("Kullanıcı adı ve şifre doğrulandı.", 200);
        }
        return respond("Bu Kullanıcı adı ve şifre ile bağlanılamadı.", 201);
    }

    public static function init($username, $password, $hostname)
    {
        $client = new Client();
        $res = $client->request('POST', env('LIMAN_CONNECTOR_SERVER') . '/new', [
            'form_params' => [
                "username" => $username,
                "password" => $password,
                "hostname" => $hostname,
                "connection_type" => "winrm"
            ],
            'timeout' => 5
        ]);
        $json = json_decode((string) $res->getBody());
        //Escape For . character in session.
        $hostname = str_replace(".", "_", $hostname);
        if (auth() && auth()->user()) {
            session()->put([
                $hostname => $json->token,
                $hostname . "_ticket" => $json->ticket_path
            ]);
        }
        return $json->token;
    }

    public static function create(Server $server, $username, $password, $user_id, $key)
    {
        $token = self::init($username, $password, $server->ip_address);
        if ($token) {
            return "OK";
        } else {
            return "NO";
        }
    }
}
