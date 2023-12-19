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
