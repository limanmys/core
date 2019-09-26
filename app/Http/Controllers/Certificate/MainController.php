<?php

namespace App\Http\Controllers\Certificate;

use App\AdminNotification;
use App\Certificate;
use App\Notification;
use App\Server;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MainController extends Controller
{

    public function verifyCert()
    {
        // Check If Certificate Already Added or not.
        if(Certificate::where([
            "server_hostname" => request('server_hostname'),
            "origin" => request('origin')
        ])->exists()){
            return respond("Bu sunucu ve port için sertifika zaten eklenmiş.",201);
        }

        $file = "liman-" . request('server_hostname') . "_" . request('origin') . ".crt";
        $cert = file_get_contents('/tmp/' . request('path'));
        $query = "echo '$cert'| sudo tee /usr/local/share/ca-certificates/" . $file;
        shell_exec($query);
        shell_exec("sudo update-ca-certificates");

        // Create Certificate Object.
        $cert = new Certificate(request()->all());
        $cert->save();

        // Update Admin Notification
        $adminNotification = AdminNotification::where('id',request('notification_id'));
        if($adminNotification){
            $adminNotification->update([
                "read" => "true"
            ]);
        }

        return respond("Sertifika Başarıyla Eklendi!",200);
    }

    public function removeCert()
    {
        $certificate = Certificate::where('id',request('certificate_id'))->first();
        if(!$certificate){
            abort(504,"Sertifika bulunamadı");
        }

        shell_exec("sudo rm /usr/local/share/ca-certificates/liman-" . $certificate->server_hostname . "_" . $certificate->origin . ".crt");
        shell_exec("sudo update-ca-certificates");

        Server::where([
            'ip_address' => $certificate->server_hostname,
            "control_port" => $certificate->origin
        ])->update([
            "enabled" => "0"
        ]);

        $certificate->delete();
        return respond("Sertifika Başarıyla Silindi!",200);

    }

    public function requestCert()
    {
        $get = stream_context_create(array("ssl" => array("capture_peer_cert" => TRUE,"allow_self_signed" => TRUE,"verify_peer" => FALSE,"verify_peer_name" => FALSE)));
        try{
            $read = stream_socket_client("ssl://" .
             request("hostname") . ":" . request('port'), $errno, $errstr, intval(env('SERVER_CONNECTION_TIMEOUT')), STREAM_CLIENT_CONNECT, $get);
        }catch(\Exception $exception){
            return respond("Sertifika Alınamadı",201);
        }
        
        $cert = stream_context_get_params($read);
        $certinfo = openssl_x509_parse($cert['options']['ssl']['peer_certificate']);
        openssl_x509_export($cert["options"]["ssl"]["peer_certificate"],$publicKey);
        $certinfo["subjectKeyIdentifier"] = $certinfo["extensions"]["subjectKeyIdentifier"];
        $certinfo["authorityKeyIdentifier"] = substr($certinfo["extensions"]["authorityKeyIdentifier"],6);
        $certinfo["validFrom_time_t"] = Carbon::createFromTimestamp($certinfo["validFrom_time_t"])->format('H:i d/m/Y');
        $certinfo["validTo_time_t"] = Carbon::createFromTimestamp($certinfo["validTo_time_t"])->format('H:i d/m/Y');
        unset($certinfo["extensions"]);
        $path = Str::random(10);
        $certinfo["path"] = $path;
        file_put_contents("/tmp/" . $path,$publicKey);
        return respond($certinfo);
    }
}
