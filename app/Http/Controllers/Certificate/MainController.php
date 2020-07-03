<?php

namespace App\Http\Controllers\Certificate;

use App\Models\AdminNotification;
use App\Models\Certificate;
use App\Models\Server;
use App\Http\Controllers\Controller;

class MainController extends Controller
{
    public function verifyCert()
    {
        // Check If Certificate Already Added or not.
        if (
            Certificate::where([
                "server_hostname" => strtolower(request('server_hostname')),
                "origin" => request('origin'),
            ])->exists()
        ) {
            return respond(
                "Bu sunucu ve port için sertifika zaten eklenmiş.",
                201
            );
        }

        // Create Certificate Object.
        $certificate = Certificate::create([
            "server_hostname" => strtolower(request('server_hostname')),
            "origin" => request('origin'),
        ]);

        $certificate->addToSystem('/tmp/' . request('path'));

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

        $certificate->removeFromSystem();

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

        $certificate->removeFromSystem();

        $certificate->addToSystem('/tmp/' . $message["path"]);

        return respond("Sertifika Başarıyla Güncellendi!");
    }
}
