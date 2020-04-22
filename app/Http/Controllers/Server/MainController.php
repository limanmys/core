<?php

namespace App\Http\Controllers\Server;

use App\Classes\Connector\SSHConnector;
use App\Classes\Connector\SSHCertificateConnector;
use App\Classes\Connector\WinRMConnector;
use App\Server;
use App\Http\Controllers\Controller;

class MainController extends Controller
{
    public function checkAccess()
    {
        $status = @fsockopen(request('hostname'),request('port'),$errno,$errstr,(intval(env('SERVER_CONNECTION_TIMEOUT')) / 1000));
        if(is_resource($status)){
            return respond("Sunucuya başarıyla erişim sağlandı.",200);
        }else{
            return respond("Sunucuya erişim sağlanamadı.",201);
        }
    }

    public function verifyName()
    {
        if(!Server::where('name',request('server_name'))->exists()){
            return respond("İsim Onaylandı.",200);
        }else{
            return respond("Bu isimde zaten bir sunucu var.",201);
        }
    }

    public function verifyKey()
    {
        hook("server_key_verify",["key" => [
            "key_type" => request('key_type'),
            "ip_address" => request('ip_address'),
            "username" => request('username'),
            "password" => request('password'),
            "port" => request('port')
        ]]);

        if(request('key_type') == "linux_ssh"){
            return SSHConnector::verify(request('ip_address'),request('username'),request('password'),request('port'));
        }else if (request('key_type') == "windows_powershell"){
            return WinRMConnector::verify(request('ip_address'),request('username'),request('password'),request('port'));
        }else if(request('key_type') == "linux_certificate"){
            return SSHCertificateConnector::verify(request('ip_address'),request('username'),request('password'),request('port'));
        }{
            return respond("Bu anahtara göre bir yapı bulunamadı.",201);
        }
    }
}
