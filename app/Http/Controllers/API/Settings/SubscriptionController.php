<?php

namespace App\Http\Controllers\API\Settings;

use App\Http\Controllers\Controller;
use App\Models\Extension;
use App\Models\GolangLicense;
use App\Models\License;
use App\Models\Server;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use mervick\aesEverywhere\AES256;

/**
 * Subscription Controller
 */
class SubscriptionController extends Controller
{
    /**
     * Subscribable extensions
     *
     * @return mixed
     */
    public function index()
    {
        return Extension::where('license_type', 'golang_standard')
            ->get();
    }

    /**
     * Servers list that extension uses
     *
     * @param Extension $extension
     * @return mixed
     */
    public function servers(Extension $extension)
    {
        return $extension->servers()->get();
    }

    /**
     * Show extension license status
     *
     * @param Extension $extension
     * @param Server $server
     * @return GolangLicense|JsonResponse
     */
    public function show(Extension $extension, Server $server)
    {
        if (! $server) {
            return new GolangLicense([]);
        }

        // Cache fetched extension license if it's valid, until it changed on redis for fast re-fetching
        $license = Cache::rememberForever('extension_'.$extension->id.'_'.$server->id.'_license', function () use ($extension, $server) {
            $output = callExtensionFunction(
                $extension,
                $server,
                [
                    'endpoint' => 'license',
                    'type' => 'get',
                ]
            );
            $parsed = new GolangLicense($output);

            return $parsed->getValid() ? $parsed : null;
        });

        return response()->json($license);
    }

    /**
     * Liman license status
     *
     * @return JsonResponse
     * @throws \Exception
     */
    public function limanLicense()
    {
        $license = License::find('00000000-0000-0000-0000-000000000000');

        if (! $license) {
            return response()->json([
                'message' => 'Lisans anahtarı bulunamadı.',
            ], 404);
        }

        $license->data = json_decode(AES256::decrypt($license->data, md5(env('APP_KEY'))));

        return response()->json($license->data);
    }

    /**
     * Set Liman License
     *
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function setLimanLicense(Request $request)
    {
        $license = $request->license;
        $license = AES256::decrypt($license, md5(env('APP_KEY')));
        if (! $license) {
            return response()->json([
                'license' => 'Lisans anahtarı geçersiz.',
            ], 422);
        }

        $license = License::updateOrCreate(
            ['id' => '00000000-0000-0000-0000-000000000000'],
            ['data' => $request->license]
        );

        $license = json_decode(trim(AES256::decrypt($license->data, md5(env('APP_KEY')))));

        return response()->json($license);
    }
}
