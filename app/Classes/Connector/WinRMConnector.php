<?php

namespace App\Classes\Connector;

use App\Key;
use App\Server;

class WinRMConnector implements Connector
{
    public function __construct(Server $server, $user_id)
    {
        ($key = Key::where([
            "user_id" => $user_id,
            "server_id" => $server->id
        ])->first()) || abort(504,"WinRM Anahtarınız yok.");
        $checkScript = "/usr/bin/python3 /liman/server/storage/winrm/winrm_validate.py '" . $server->ip_address . "' '" 
        . env('KEYS_PATH') . "windows" . DIRECTORY_SEPARATOR . $user_id . $server->id . "_cert.pem' '"
        . env('KEYS_PATH') . "windows" . DIRECTORY_SEPARATOR . $user_id . $server->id . "_prv.pem' '" . md5(env('APP_KEY') . auth()->id()). "'";
        $output = shell_exec($checkScript);
        if($output != "ok\n"){
            abort(504,"Sertifikanız geçerli değil.");
        }
        return true;
    }

    public function __destruct()
    {

    }

    public function execute($command)
    {
        $executeScript = "/usr/bin/python3 /liman/server/storage/winrm/winrm_execute.py '" . server()->ip_address . "' '" 
        . env('KEYS_PATH') . "windows" . DIRECTORY_SEPARATOR . auth()->id() . server()->id . "_cert.pem' '"
        . env('KEYS_PATH') . "windows" . DIRECTORY_SEPARATOR . auth()->id() . server()->id . "_prv.pem' '" . md5(env('APP_KEY') . auth()->id()) . "'";
        return shell_exec($executeScript . " \"" . $command . "\"");
;    }

    public function sendFile($localPath, $remotePath, $permissions = 0644)
    {
        $receiveFile = "/usr/bin/python3 /liman/server/storage/winrm/winrm_sendfile.py '" . server()->ip_address . "' '"
            . env('KEYS_PATH') . "windows" . DIRECTORY_SEPARATOR . auth()->id() . server()->id . "_cert.pem' '"
            . env('KEYS_PATH') . "windows" . DIRECTORY_SEPARATOR . auth()->id() . server()->id . "_prv.pem' '" . md5(env('APP_KEY') . auth()->id()) . "'" .
            " '$localPath' '$remotePath'";
        shell_exec($receiveFile);
        return true;
    }

    public function receiveFile($localPath, $remotePath)
    {
        $receiveFile = "/usr/bin/python3 /liman/server/storage/winrm/winrm_getfile.py '" . server()->ip_address . "' '" 
        . env('KEYS_PATH') . "windows" . DIRECTORY_SEPARATOR . auth()->id() . server()->id . "_cert.pem' '"
        . env('KEYS_PATH') . "windows" . DIRECTORY_SEPARATOR . auth()->id() . server()->id . "_prv.pem' '" . md5(env('APP_KEY') . auth()->id()) . "'" .
        " '$remotePath' '$localPath'";
        shell_exec($receiveFile);
        return is_file($localPath);
    }

    public function runScript($script,$parameters, $extra = null)
    {

    }

    public static function verify($ip_address, $username, $password, $port)
    {
        $command = "timeout 5 /usr/bin/python3 " . storage_path('winrm/winrm_verify.py' . " '"
                . $ip_address . "' '" . $username . "' '" . $password . "'");
        if(shell_exec($command) == "OK\n"){
            return respond("Kullanıcı adı ve şifre doğrulandı.",200);
        }else{
            return respond("Bu Kullanıcı adı ve şifre ile bağlanılamadı.",201);
        }
    }

    public static function create(Server $server, $username, $password, $user_id, $key)
    {
        $beforeScript = "/usr/bin/python3 /liman/server/storage/winrm/winrm_cert.py before '" . $server->ip_address . "' '$username' '$password' '" 
        . env('KEYS_PATH') . "windows" . DIRECTORY_SEPARATOR . $user_id . $server->id . "_cert.pem' '"
        . env('KEYS_PATH') . "windows" . DIRECTORY_SEPARATOR . $user_id . $server->id . "_prv.pem' '" . md5(env('APP_KEY') . auth()->id()). "'";
        $beforeOutput = shell_exec($beforeScript);

        if($beforeOutput != "ok\n"){
            $key->delete();
            abort(504,($beforeOutput) ? $beforeOutput : "Bir Hata Oluştu.");
        }

    	$runScript = "/usr/bin/python3 /liman/server/storage/winrm/winrm_cert.py run '" . $server->ip_address . "' '$username' '$password' '" 
        . env('KEYS_PATH') . "windows" . DIRECTORY_SEPARATOR . $user_id . $server->id . "_cert.pem' '"
        . env('KEYS_PATH') . "windows" . DIRECTORY_SEPARATOR . $user_id . $server->id . "_prv.pem' '" . md5(env('APP_KEY') . auth()->id()) . "'";
        $output = shell_exec($runScript);
    	return "OK";
    }
}