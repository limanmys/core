<?php

namespace App\Classes\Connector;

class WinRMConnector implements Connector
{
    public function __construct(\App\Server $server,$user_id)
    {
        ($key = \App\Key::where([
            "user_id" => $user_id,
            "server_id" => $server->_id
        ])->first()) || abort(504,"WinRM Anahtarınız yok.");
        $checkScript = "/usr/bin/python3 /liman/server/storage/winrm_validate.py '" . $server->ip_address . "' '" 
        . storage_path('keys/windows') . DIRECTORY_SEPARATOR . $user_id . "_cert.pem' '" 
        . storage_path('keys/windows') . DIRECTORY_SEPARATOR . $user_id . "_prv.pem' '" . md5(env('APP_KEY') . auth()->id()). "'";
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
        $executeScript = "/usr/bin/python3 /liman/server/storage/winrm_execute.py '" . server()->ip_address . "' '" 
        . storage_path('keys/windows') . DIRECTORY_SEPARATOR . auth()->user()->_id . "_cert.pem' '" 
        . storage_path('keys/windows') . DIRECTORY_SEPARATOR . auth()->user()->_id . "_prv.pem' '" . md5(env('APP_KEY') . auth()->id()) . "'";
        return shell_exec($executeScript . " " . $command)
;    }

    public function sendFile($localPath, $remotePath, $permissions = 0644)
    {
        $receiveFile = "/usr/bin/python3 /liman/server/storage/winrm_sendfile.py '" . server()->ip_address . "' '"
            . storage_path('keys/windows') . DIRECTORY_SEPARATOR . auth()->user()->_id . "_cert.pem' '"
            . storage_path('keys/windows') . DIRECTORY_SEPARATOR . auth()->user()->_id . "_prv.pem' '" . md5(env('APP_KEY') . auth()->id()) . "'" .
            " '$remotePath' '$localPath'";
        shell_exec($receiveFile);
        return true;
    }

    public function receiveFile($localPath, $remotePath)
    {
        $receiveFile = "/usr/bin/python3 /liman/server/storage/winrm_getfile.py '" . server()->ip_address . "' '" 
        . storage_path('keys/windows') . DIRECTORY_SEPARATOR . auth()->user()->_id . "_cert.pem' '" 
        . storage_path('keys/windows') . DIRECTORY_SEPARATOR . auth()->user()->_id . "_prv.pem' '" . md5(env('APP_KEY') . auth()->id()) . "'" .
        " '$remotePath' '$localPath'";
        shell_exec($receiveFile);
        return is_file($localPath);
    }

    public function runScript($script,$parameters, $extra = null)
    {

    }

    public static function create(\App\Server $server, $username, $password, $user_id,$key)
    {
        $beforeScript = "/usr/bin/python3 /liman/server/storage/winrm_cert.py before '" . $server->ip_address . "' '$username' '$password' '" 
        . storage_path('keys/windows') . DIRECTORY_SEPARATOR . $user_id . "_cert.pem' '" 
        . storage_path('keys/windows') . DIRECTORY_SEPARATOR . $user_id . "_prv.pem' '" . md5(env('APP_KEY') . auth()->id()). "'";
        $beforeOutput = shell_exec($beforeScript);
        if($beforeOutput != "ok\n"){
            $key->delete();
            $server->delete();
            abort(504,$beforeOutput);
        }

    	$runScript = "/usr/bin/python3 /liman/server/storage/winrm_cert.py run '" . $server->ip_address . "' '$username' '$password' '" 
        . storage_path('keys/windows') . DIRECTORY_SEPARATOR . $user_id . "_cert.pem' '" 
        . storage_path('keys/windows') . DIRECTORY_SEPARATOR . $user_id . "_prv.pem' '" . md5(env('APP_KEY') . auth()->id()) . "'";
        $output = shell_exec($runScript);
    	return true;
    }
}