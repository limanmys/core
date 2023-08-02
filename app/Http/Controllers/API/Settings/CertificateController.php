<?php

namespace App\Http\Controllers\API\Settings;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\System\Command;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CertificateController extends Controller
{
    public function index()
    {
        return Certificate::orderBy('updated_at', 'desc')->get();
    }

    /**
     * Create certificate for determined host
     *
     * @return JsonResponse|Response
     */
    public function create(Request $request)
    {
        // Check If Certificate Already Added or not.
        if (
            Certificate::where([
                'server_hostname' => strtolower((string) $request->hostname),
                'origin' => $request->origin,
            ])->exists()
        ) {
            return response()->json([
                'message' => 'Bu sunucu ve port için sertifika zaten eklenmiş.',
            ], Response::HTTP_CONFLICT);
        }

        [$flag, $message] = retrieveCertificate(
            strtolower((string) $request->hostname),
            $request->origin
        );

        if (! $flag) {
            return response()->json([
                'message' => $message,
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // Create Certificate Object.
        $certificate = Certificate::create([
            'server_hostname' => strtolower((string) $request->hostname),
            'origin' => $request->origin,
        ]);

        $certificate->addToSystem('/tmp/' . request('path'));

        return response()->json([
            'message' => 'Sertifika başarıyla eklendi.',
        ]);
    }

    /**
     * Delete certificate
     *
     * @return JsonResponse|Response
     */
    public function delete(Request $request)
    {
        $certificate = Certificate::where(
            'id',
            $request->id
        )->first();

        if (! $certificate) {
            return response()->json([
                'message' => 'Sertifika bulunamadı.',
            ], Response::HTTP_NOT_FOUND);
        }

        $certificate->removeFromSystem();

        $certificate->delete();

        return response()->json([
            'message' => 'Sertifika başarıyla silindi.',
        ]);
    }

    /**
     * Get certificate details for determined server
     *
     * @param Request $request
     * @return JsonResponse|Response
     */
    public function information(Certificate $certificate)
    {
        $certificateFile = Command::runLiman('cat /usr/local/share/ca-certificates/liman-{:ipAddress}_{:port}.crt', [
            'ipAddress' => $certificate->server_hostname,
            'port' => $certificate->origin,
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

        return response()->json($certinfo);
    }

    /**
     * Retrieve certificate from remote end
     *
     * @return JsonResponse|Response
     */
    public function retrieve(Request $request)
    {
        validate([
            'hostname' => 'required',
            'port' => 'required|numeric|min:1|max:65537',
        ]);

        [$flag, $message] = retrieveCertificate(
            $request->hostname,
            $request->port
        );
        
        return response()->json([
            'message' => $message,
        ], $flag ? Response::HTTP_OK : Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * Renew certificate for server
     *
     * @return JsonResponse|Response
     */
    public function update(Request $request)
    {
        $certificate = Certificate::where(
            'id',
            $request->id
        )->first();
        if (! $certificate) {
            return response()->json([
                'message' => 'Sertifika bulunamadı.',
            ], Response::HTTP_NOT_FOUND);
        }
        [$flag, $message] = retrieveCertificate(
            $certificate->server_hostname,
            $certificate->origin
        );
        if (! $flag) {
            return response()->json([
                'message' => $message,
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $certificate->removeFromSystem();

        $certificate->addToSystem('/tmp/' . $message['path']);

        return response()->json([
            'message' => 'Sertifika başarıyla güncellendi.',
        ]);
    }
}
