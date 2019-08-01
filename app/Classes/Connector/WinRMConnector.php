<?php

namespace App\Classes\Connector;

use App\Key;
use App\Server;
use Illuminate\Support\Facades\Log;

class WinRMConnector implements Connector
{
    public function __construct(Server $server, $user_id)
    {
        ($key = Key::where([
            "user_id" => $user_id,
            "server_id" => $server->id
        ])->first()) || abort(504,"WinRM Anahtarınız yok.");
        $path = "/liman/server/storage/winrm/winrm_validate";
        shell_exec("sudo chmod +x $path");
        $checkScript = "$path '" . $server->ip_address . "' '"
        . env('KEYS_PATH') . "windows" . DIRECTORY_SEPARATOR . $user_id . $server->id . "_cert.pem' '"
        . env('KEYS_PATH') . "windows" . DIRECTORY_SEPARATOR . $user_id . $server->id . "_prv.pem' '" . md5(env('APP_KEY') . $user_id). "'";
        $output = shell_exec($checkScript);
        Log::debug($checkScript);
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
        $path = "/liman/server/storage/winrm/winrm_execute";
        shell_exec("sudo chmod +x $path");
        $executeScript = "$path '" . server()->ip_address . "' '"
        . env('KEYS_PATH') . "windows" . DIRECTORY_SEPARATOR . auth()->user()->id . server()->id . "_cert.pem' '"
        . env('KEYS_PATH') . "windows" . DIRECTORY_SEPARATOR . auth()->user()->id . server()->id . "_prv.pem' '" . md5(env('APP_KEY') . auth()->user()->id) . "'";
        Log::debug($executeScript . " \"" . $command . "\"");
        return shell_exec($executeScript . " \"" . $command . "\"");
    }

    public function sendFile($localPath, $remotePath, $permissions = 0644)
    {
        $path = "/liman/server/storage/winrm/winrm_sendfile";
        shell_exec("sudo chmod +x $path");
        $receiveFile = "$path '" . server()->ip_address . "' '"
            . env('KEYS_PATH') . "windows" . DIRECTORY_SEPARATOR . auth()->user()->id . server()->id . "_cert.pem' '"
            . env('KEYS_PATH') . "windows" . DIRECTORY_SEPARATOR . auth()->user()->id . server()->id . "_prv.pem' '" . md5(env('APP_KEY') . auth()->user()->id) . "'" .
            " '$localPath' '$remotePath'";
        shell_exec($receiveFile);
        Log::debug($receiveFile);
        return true;
    }

    public function receiveFile($localPath, $remotePath)
    {
        $path = "/liman/server/storage/winrm/winrm_getfile";
        shell_exec("sudo chmod +x $path");
        $receiveFile = "$path '" . server()->ip_address . "' '"
        . env('KEYS_PATH') . "windows" . DIRECTORY_SEPARATOR . auth()->user()->id . server()->id . "_cert.pem' '"
        . env('KEYS_PATH') . "windows" . DIRECTORY_SEPARATOR . auth()->user()->id . server()->id . "_prv.pem' '" . md5(env('APP_KEY') . auth()->user()->id) . "'" .
        " '$remotePath' '$localPath'";
        shell_exec($receiveFile);
        Log::debug($receiveFile);
        return is_file($localPath);
    }

    public function runScript($script,$parameters, $extra = null)
    {

    }

    public static function verify($ip_address, $username, $password, $port)
    {
        $path = "/liman/server/storage/winrm/winrm_verify";
        shell_exec("sudo chmod +x $path");
        $command = "timeout 5 $path '". $ip_address . "' '" . $username . "' '" . $password . "'";
        if(shell_exec($command) == "OK\n"){
            return respond("Kullanıcı adı ve şifre doğrulandı.",200);
        }else{
            return respond("Bu Kullanıcı adı ve şifre ile bağlanılamadı.",201);
        }
    }

    public static function create(Server $server, $username, $password, $user_id, $key)
    {
        $path = "/liman/server/storage/winrm/winrm_cert";
        shell_exec("sudo chmod +x $path");
        $beforeScript = "$path before '" . $server->ip_address . "' '$username' '$password' '"
        . env('KEYS_PATH') . "windows" . DIRECTORY_SEPARATOR . $user_id . $server->id . "_cert.pem' '"
        . env('KEYS_PATH') . "windows" . DIRECTORY_SEPARATOR . $user_id . $server->id . "_prv.pem' '" . md5(env('APP_KEY') . auth()->user()->id). "'";
        $beforeOutput = shell_exec($beforeScript);
        Log::debug($beforeScript);
        if($beforeOutput != "ok\n"){
            $key->delete();
            abort(504,($beforeOutput) ? $beforeOutput : "Bir Hata Oluştu.");
        }

    	$runScript = "$path run '" . $server->ip_address . "' '$username' '$password' '"
        . env('KEYS_PATH') . "windows" . DIRECTORY_SEPARATOR . $user_id . $server->id . "_cert.pem' '"
        . env('KEYS_PATH') . "windows" . DIRECTORY_SEPARATOR . $user_id . $server->id . "_prv.pem' '" . md5(env('APP_KEY') . auth()->user()->id) . "'";
        Log::debug($runScript);
        $output = shell_exec($runScript);
    	return "OK";
    }
}