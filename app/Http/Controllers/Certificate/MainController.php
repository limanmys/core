<?php

namespace App\Http\Controllers\Certificate;

use App\AdminNotification;
use App\Certificate;
use App\Notification;
use App\Server;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;

class MainController extends Controller
{

    public function verifyCert()
    {
        // Check If Certificate Already Added or not.
        if(Certificate::where([
            "server_hostname" => request('hostname'),
            "origin" => request('origin')
        ])->exists()){
            return respond("Bu sunucu ve port için sertifika zaten eklenmiş.",201);
        }

        $file = "liman-" . request('hostname') . "_" . request('origin') . ".crt";
        $cert = request('certificate');
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
        $hostname = request('hostname');
        $port = request('port');
        $output = shell_exec("timeout 5 echo -n | openssl s_client -connect $hostname:$port | sed -ne '/-BEGIN CERTIFICATE-/,/-END CERTIFICATE-/p'");
        return respond($output);
    }
}
