<?php

namespace App\Http\Controllers\API\Settings;

use App\Http\Controllers\Controller;
use App\System\Command;
use Illuminate\Http\Request;

class DNSController extends Controller
{
    /**
     * Get DNS information on Liman
     *
     * @return JsonResponse
     */
    public function getDNSServers()
    {
        $data = Command::runSystem('grep nameserver /etc/resolv.conf | grep -v "#" | grep nameserver');
        $arr = explode("\n", (string) $data);
        $arr = array_filter($arr);
        $clean = [];
        foreach ($arr as $ip) {
            if ($ip == '') {
                continue;
            }
            $temp = explode(' ', trim($ip));
            if (count($temp) == 1) {
                continue;
            }
            array_push($clean, $temp[1]);
        }

        return response()->json([
            'dns1' => $clean[0] ?? '',
            'dns2' => $clean[1] ?? '',
            'dns3' => $clean[2] ?? '',
        ]);
    }

    /**
     * Set DNS settings on Liman
     *
     * @return JsonResponse
     * @throws GuzzleException
     */
    public function setDNSServers(Request $request)
    {
        validate([
            'dns1' => 'required|ip'
        ]);

        if (strlen($request->dns2) > 2) {
            validate([
                'dns2' => 'ip'
            ]);
        }

        if (strlen($request->dns3) > 2) {
            validate([
                'dns3' => 'ip'
            ]);
        }

        $flag = rootSystem()->dnsUpdate(
            $request->dns1,
            $request->dns2,
            $request->dns3
        );
        
        return response()->json([
            'message' => $flag ? 'DNS ayarları başarıyla güncellendi.' : 'DNS ayarları güncellenirken bir hata oluştu.',
        ], $flag ? 200 : 500);
    }
}
