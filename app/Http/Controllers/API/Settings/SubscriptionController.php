<?php

namespace App\Http\Controllers\API\Settings;

use App\Http\Controllers\Controller;
use App\Models\Extension;
use App\Models\GolangLicense;
use App\Models\License;
use Illuminate\Http\Request;
use mervick\aesEverywhere\AES256;

class SubscriptionController extends Controller
{
    public function index()
    {
        $subscribable = Extension::where('license_type', 'golang_standard')
            ->get();

        return response()->json($subscribable);
    }

    public function show(Extension $extension)
    {
        $server = $extension->servers()->first();
        if (!$server) {
            return $extension;
        }

        $output = callExtensionFunction(
            $extension,
            $server,
            [
                'endpoint' => 'license',
                'type' => 'get',
            ]
        );
        $license = new GolangLicense($output);

        return response()->json($license);
    }

    public function limanLicense()
    {
        return response()->json([
            "issuer" => "HAVELSAN A.Ş.", 
            "issued" => "Açıklab Yazılım Geliştirme Takım Liderliği", 
            "issued_no" => "0001", 
            "membership_start_time" => 1435067385000, 
            "coverage_start" => 1592920185000, 
            "coverage_end" => 1719150585000, 
            "package_type" => "Full Premium Paket"
        ]);
        
        // TODO: License should be encrypted
        // Decrypt license
        $license = License::find("00000000-0000-0000-0000-000000000000");
        if ($license) {
            $license->data = AES256::decrypt($license->data, md5(env('APP_KEY')));

        }
        return response()->json($license);
    }

    public function setLimanLicense(Request $request)
    {
        // Decrypt before adding and if decrypting is successful go on
        $license = License::updateOrCreate(
            ['id' => "00000000-0000-0000-0000-000000000000"],
            ['data' => $request->license]
        );

        return response()->json($license);
    }
}
