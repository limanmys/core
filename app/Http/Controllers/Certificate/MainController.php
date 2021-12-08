<?php

namespace App\Http\Controllers\Certificate;

use App\Models\AdminNotification;
use App\Models\Certificate;
use App\Http\Controllers\Controller;
use App\System\Command;
use Carbon\Carbon;
use Illuminate\Http\Request;

class MainController extends Controller
{
    /**
     * @api {post} /sunucu/sertifikaOnayi Add SSL Sertificate
     * @apiName Add SSL Sertificate
     * @apiGroup Certificate
     *
     * @apiParam {String} server_hostname Server's hostname.
     * @apiParam {String} origin Target port to retrieve certificate.
     * @apiParam {String} notification_id Request Notification Id (OPTIONAL)
     *
     * @apiSuccess {JSON} message Message with status.
     */
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

        list($flag, $message) = retrieveCertificate(
            strtolower(request('server_hostname')),
            request('origin')
        );

        if (!$flag) {
            return respond(
                $message,
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

    /**
     * @api {post} /sunucu/sertifikaSil Remove SSL Sertificate
     * @apiName Remove SSL Sertificate
     * @apiGroup Certificate
     *
     * @apiParam {String} certificate_id Certificate Id.
     *
     * @apiSuccess {JSON} message Message with status.
     */
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

    public function getCertificateInfo(Request $request)
    {
        $certificateFile = Command::runLiman("cat /usr/local/share/ca-certificates/liman-{:ipAddress}_{:port}.crt", [
            "ipAddress" => $request->hostname,
            "port" => $request->port
        ]);

        $certinfo = openssl_x509_parse($certificateFile);
        $certinfo["subjectKeyIdentifier"] = array_key_exists(
            "subjectKeyIdentifier",
            $certinfo["extensions"]
        )
            ? $certinfo["extensions"]["subjectKeyIdentifier"]
            : "";
        $certinfo["authorityKeyIdentifier"] = array_key_exists(
            "authorityKeyIdentifier",
            $certinfo["extensions"]
        )
            ? substr($certinfo["extensions"]["authorityKeyIdentifier"], 6)
            : "";
        $certinfo["validFrom_time_t"] = Carbon::createFromTimestamp(
            $certinfo["validFrom_time_t"]
        )->format('H:i d/m/Y');
        $certinfo["validTo_time_t"] = Carbon::createFromTimestamp(
            $certinfo["validTo_time_t"]
        )->format('H:i d/m/Y');
        unset($certinfo["extensions"]);
        
        return respond($certinfo);
    }

    /**
     * @api {post} /sunucu/sertifikaTalep Request SSL Sertificate
     * @apiName Request SSL Sertificate
     * @apiGroup Certificate
     *
     * @apiParam {String} hostname Target Server' Hostname.
     * @apiParam {String} port Target Server' Port.
     *
     * @apiSuccess {Array} array Requested certificate information..
     */
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

    /**
     * @api {post} /sunucu/sertifikaGuncelle Renew SSL Sertificate
     * @apiName Renew SSL Sertificate
     * @apiGroup Certificate
     *
     * @apiParam {String} certificate_id Certificate id to renew.
     *
     * @apiSuccess {JSON} message Message with status.
     */
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
