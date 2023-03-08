<?php

namespace App\Http\Controllers\Certificate;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use App\Models\Certificate;
use App\System\Command;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Certificate Main Controller
 *
 * @extends Controller
 */
class MainController extends Controller
{
    /**
     * Create certificate for determined host
     *
     * @return JsonResponse|Response
     */
    public function verifyCert()
    {
        // Check If Certificate Already Added or not.
        if (
            Certificate::where([
                'server_hostname' => strtolower((string) request('server_hostname')),
                'origin' => request('origin'),
            ])->exists()
        ) {
            return respond(
                'Bu sunucu ve port için sertifika zaten eklenmiş.',
                201
            );
        }

        [$flag, $message] = retrieveCertificate(
            strtolower((string) request('server_hostname')),
            request('origin')
        );

        if (! $flag) {
            return respond(
                $message,
                201
            );
        }

        // Create Certificate Object.
        $certificate = Certificate::create([
            'server_hostname' => strtolower((string) request('server_hostname')),
            'origin' => request('origin'),
        ]);

        $certificate->addToSystem('/tmp/' . request('path'));

        // Update Admin Notification
        AdminNotification::where('id', request('notification_id'))->update([
            'read' => 'true',
        ]);

        return respond('Sertifika Başarıyla Eklendi!', 200);
    }

    /**
     * Delete certificate
     *
     * @return JsonResponse|Response
     */
    public function removeCert()
    {
        $certificate = Certificate::where(
            'id',
            request('certificate_id')
        )->first();
        if (! $certificate) {
            abort(504, 'Sertifika bulunamadı');
        }

        $certificate->removeFromSystem();

        $certificate->delete();

        return respond('Sertifika Başarıyla Silindi!', 200);
    }

    /**
     * Get certificate details for determined server
     *
     * @param Request $request
     * @return JsonResponse|Response
     */
    public function getCertificateInfo(Request $request)
    {
        $certificateFile = Command::runLiman('cat /usr/local/share/ca-certificates/liman-{:ipAddress}_{:port}.crt', [
            'ipAddress' => $request->hostname,
            'port' => $request->port,
        ]);

        $certinfo = openssl_x509_parse($certificateFile);
        $certinfo['subjectKeyIdentifier'] = array_key_exists(
            'subjectKeyIdentifier',
            $certinfo['extensions']
        )
            ? $certinfo['extensions']['subjectKeyIdentifier']
            : '';
        $certinfo['authorityKeyIdentifier'] = array_key_exists(
            'authorityKeyIdentifier',
            $certinfo['extensions']
        )
            ? substr((string) $certinfo['extensions']['authorityKeyIdentifier'], 6)
            : '';
        $certinfo['validFrom_time_t'] = Carbon::createFromTimestamp(
            $certinfo['validFrom_time_t']
        )->format('H:i d/m/Y');
        $certinfo['validTo_time_t'] = Carbon::createFromTimestamp(
            $certinfo['validTo_time_t']
        )->format('H:i d/m/Y');
        unset($certinfo['extensions']);

        return respond($certinfo);
    }

    /**
     * Retrieve certificate from remote end
     *
     * @return JsonResponse|Response
     */
    public function requestCert()
    {
        validate([
            'hostname' => 'required',
            'port' => 'required|numeric|min:1|max:65537',
        ]);

        [$flag, $message] = retrieveCertificate(
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
     * Renew certificate for server
     *
     * @return JsonResponse|Response
     */
    public function updateCert()
    {
        $certificate = Certificate::where(
            'id',
            request('certificate_id')
        )->first();
        if (! $certificate) {
            return respond('Sertifika bulunamadı', 201);
        }
        [$flag, $message] = retrieveCertificate(
            $certificate->server_hostname,
            $certificate->origin
        );
        if (! $flag) {
            return respond($message, 201);
        }

        $certificate->removeFromSystem();

        $certificate->addToSystem('/tmp/' . $message['path']);

        return respond('Sertifika Başarıyla Güncellendi!');
    }
}
