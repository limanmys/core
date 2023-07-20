<?php

namespace App\Http\Controllers\API\Settings;

use App\Http\Controllers\Controller;
use App\Models\Extension;
use App\Models\GolangLicense;
use App\Models\License;
use App\Models\Server;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use mervick\aesEverywhere\AES256;

class SubscriptionController extends Controller
{
    public function index()
    {
        $subscribable = Extension::where('license_type', 'golang_standard')
            ->get();

        return response()->json($subscribable);
    }

    public function servers(Extension $extension)
    {
        $servers = $extension->servers()->get();

        return response()->json($servers);
    }

    public function show(Extension $extension, Server $server)
    {
        if (! $server) {
            return new GolangLicense([]);
        }
        
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

    public function limanLicense()
    {
        $license = License::find('00000000-0000-0000-0000-000000000000');
        if ($license) {
            $license->data = json_decode(AES256::decrypt($license->data, md5(env('APP_KEY'))));
        } else {
            return response()->json([
                'message' => 'Lisans anahtarı bulunamadı.',
            ], 404);
        }

        return response()->json($license->data);
    }

    public function setLimanLicense(Request $request)
    {
        $license = $request->license;
        $license = AES256::decrypt($license, md5(env('APP_KEY')));
        if (! $license) {
            return response()->json([
                'message' => 'Lisans anahtarı geçersiz.',
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
