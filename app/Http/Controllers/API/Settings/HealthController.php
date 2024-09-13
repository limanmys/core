<?php

namespace App\Http\Controllers\API\Settings;

use App\Http\Controllers\Controller;
use App\Jobs\HighAvailabilitySyncer;
use App\System\Command;
use App\Models\User;
use Illuminate\Contracts\Bus\Dispatcher;

class HealthController extends Controller
{
    public function index()
    {
        /**
         * Health Check Steps
         * 
         * 1. Disable default admin account
         * 2. Check if TLS and SSL is enabled
         * 3. Check if High availability service is enabled
         * 4. DNS check
         * 5. Check if there is a valid SSL certificate or self signed
         * 
         */

        $admin_account = User::where('email', 'administrator@liman.dev')->first() ? false : true;
        $high_availability_service = (bool) env('HIGH_AVAILABILITY_MODE', false);
        $dns_check = Command::runSystem("cat /etc/resolv.conf | grep nameserver >/dev/null && echo 1 || echo 0") === "1" ? true : false;
        $ssl_check = Command::runSystem("openssl x509 -checkend 86400 -noout -in /liman/certs/liman.crt") == "Certificate will expire" ? false : true;
        $self_signed = Command::runSystem("openssl x509 -noout -in /liman/certs/liman.crt -issuer | grep 'CN = liman' | grep 'O = Havelsan' >/dev/null && echo 1 || echo 0") === "1" ? false : true;
        
        return response()->json([
            "admin_account" => $admin_account,
            "high_availability_service" => $high_availability_service,
            "dns_check" => $dns_check,
            "ssl_check" => $ssl_check,
            "self_signed" => $self_signed
        ]);
    }

    public function manualHighAvailabilitySync()
    {
        $job = (new HighAvailabilitySyncer())
                ->onQueue('high_availability_syncer');
        app(Dispatcher::class)->dispatch($job);

        return response()->json("success");
    }
}
