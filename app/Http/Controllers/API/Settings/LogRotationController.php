<?php

namespace App\Http\Controllers\API\Settings;

use App\Http\Controllers\Controller;
use App\System\Command;
use Illuminate\Http\Request;

class LogRotationController extends Controller
{
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

        $template = '$ModLoad imfile
$InputFileName /liman/logs/liman.log
$InputFileTag liman_log:
$InputFileStateFile liman_log
$InputFileFacility local7
$InputRunFileMonitor

$InputFileName /liman/logs/liman_new.log
$InputFileTag engine_log:
$InputFileStateFile engine_log
$InputFileFacility local7
$InputRunFileMonitor

local7.liman_log <SERVERADDR>
local7.engine_log <SERVERADDR>';

        $template = str_replace(
            '<SERVERADDR>',
            (request('type') == 'tcp' ? '@@' : '@') .
            request('ip_address') .
            ':' .
            request('port'),
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
