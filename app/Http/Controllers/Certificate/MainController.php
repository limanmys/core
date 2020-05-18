<?php

namespace App\Http\Controllers\Certificate;

use App\AdminNotification;
use App\Certificate;
use App\Server;
use App\Http\Controllers\Controller;

class MainController extends Controller
{
    public function verifyCert()
    {
        // Check If Certificate Already Added or not.
        if (
            Certificate::where([
                "server_hostname" => request('server_hostname'),
                "origin" => request('origin'),
            ])->exists()
        ) {
            return respond(
                "Bu sunucu ve port için sertifika zaten eklenmiş.",
                201
            );
        }

        $file =
            "liman-" .
            request('server_hostname') .
            "_" .
            request('origin') .
            ".crt";
        $cert = file_get_contents('/tmp/' . request('path'));
        shell_exec(
            "echo '$cert'| sudo tee /usr/local/share/ca-certificates/" . strtolower($file)
        );
        shell_exec("sudo update-ca-certificates");

        // Create Certificate Object.
        $cert = Certificate::create(request()->all());

        // Update Admin Notification
        AdminNotification::where('id', request('notification_id'))->update([
            "read" => "true",
        ]);

        return respond("Sertifika Başarıyla Eklendi!", 200);
    }

    public function removeCert()
    {
        $certificate = Certificate::where(
            'id',
            request('certificate_id')
        )->first();
        if (!$certificate) {
            abort(504, "Sertifika bulunamadı");
        }

        shell_exec(
            "sudo rm /usr/local/share/ca-certificates/liman-" .
                $certificate->server_hostname .
                "_" .
                $certificate->origin .
                ".crt"
        );
        shell_exec("sudo update-ca-certificates");

        Server::where([
            'ip_address' => $certificate->server_hostname,
            "control_port" => $certificate->origin,
        ])->update([
            "enabled" => "0",
        ]);

        $certificate->delete();
        return respond("Sertifika Başarıyla Silindi!", 200);
    }

    public function requestCert()
    {
        list($flag, $message) = retrieveCertificate(
            request('hostname'),
            request('port')
        );
        if ($flag) {
            return respond($message, 200);
        } else {
            return respond($message, 201);
        }
    }

    public function updateCert()
    {
        $certificate = Certificate::where(
            'id',
            request('certificate_id')
        )->first();
        if (!$certificate) {
            return respond("Sertifika bulunamadı", 201);
        }
        list($flag, $message) = retrieveCertificate(
            $certificate->server_hostname,
            $certificate->origin
        );
        if (!$flag) {
            return respond($message, 201);
        }
        $file =
            "liman-" .
            $certificate->server_hostname .
            "_" .
            $certificate->origin .
            ".crt";
        shell_exec('sudo rm /usr/local/share/ca-certificates/ ' . $file);
        shell_exec("sudo update-ca-certificates -f");
        $cert = file_get_contents('/tmp/' . $message["path"]);
        shell_exec(
            "echo '$cert'| sudo tee /usr/local/share/ca-certificates/" . $file
        );
        $certificate->save();
        shell_exec("sudo update-ca-certificates -f");
        return respond("Sertifika Başarıyla Güncellendi!");
    }
}
