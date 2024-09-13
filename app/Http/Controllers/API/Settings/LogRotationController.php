<?php

namespace App\Http\Controllers\API\Settings;

use App\Http\Controllers\Controller;
use App\System\Command;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class LogRotationController extends Controller
{
    /**
     * Get log rotation configuration
     * 
     * @return JsonResponse
     */
    public function getConfiguration()
    {
        try {
            $config = Command::runSystem('cat /etc/rsyslog.d/liman.conf');

            $config = explode("\n", $config);
    
            $type = explode('protocol="', $config[2]);
            $type = explode('"', $type[1])[0];
    
            $ip = explode('target="', $config[2]);
            $ip = explode('"', $ip[1])[0];
    
            $port = explode('port="', $config[2]);
            $port = explode('"', $port[1])[0];
    
            return response()->json([
                'type' => $type,
                'ip_address' => $ip,
                'port' => $port,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'type' => 'tcp',
                'ip_address' => '',
                'port' => '',
            ]);
        }
    }

    /**
     * Set log rotation configuration
     *
     * @param Request $request
     * @return JsonResponse
     * @throws GuzzleException
     */
    public function saveConfiguration(Request $request)
    {
        validate([
            'type' => 'required|in:tcp,udp',
            'ip_address' => 'required|min:3',
            'port' => 'required|numeric|between:1,65535'
        ]);

        if ((bool) env('CONTAINER_MODE', false)) {
            return response()->json([
                'ip_address' => ['Bu özellik konteyner modunda kullanılamaz.'],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Check if the port is open
        // Disable fsockopen error reporting
        error_reporting(0);
        try {
            $connection = @fsockopen($request->ip_address, $request->port, $errno, $errstr, 0.1);
            if (! $connection) {
                return response()->json([
                    'ip_address' => ['Sunucuya erişim sağlanamadı.'],
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            } else {
                fclose($connection);
            }
        } catch (\Throwable $e) {
            return response()->json([
                'ip_address' => ['Sunucuya erişim sağlanamadı.'],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } 
        // Restore error reporting
        error_reporting(E_ALL);

        $template = 'module(load="imfile")
input(type="imfile" File="/liman/logs/liman_new.log" Tag="engine" ruleset="remote")
ruleset(name="remote"){action(type="omfwd" target="<TARGET>" port="<PORT>" protocol="<PROTOCOL>")}
input(type="imfile" File="/liman/logs/liman.log" Tag="liman" ruleset="remote")
ruleset(name="remote"){action(type="omfwd" target="<TARGET>" port="<PORT>" protocol="<PROTOCOL>")}';

        $template = str_replace(
            '<TARGET>',
            $request->ip_address,
            $template
        );

        $template = str_replace(
            '<PORT>',
            $request->port,
            $template
        );

        $template = str_replace(
            '<PROTOCOL>',
            $request->type,
            $template
        );

        Command::runSystem("echo ':text:' > /etc/rsyslog.d/liman.conf", [
            'text' => $template,
        ]);

        Command::runSystem('systemctl restart rsyslog');

        return response()->json([
            'status' => true,
            'message' => 'Ayarlar kaydedildi.',
        ]);
    }
}
