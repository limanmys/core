<?php

namespace App\Http\Controllers\API\Server;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\System\Command;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ServiceController extends Controller
{
    public function __construct()
    {
        if (!isset(auth('api')->user()->id)) {
            return respond('Please log-in again.', Response::HTTP_UNAUTHORIZED);
        }

        if (!Permission::can(auth('api')->user()->id, 'liman', 'id', 'server_services')) {
            return respond('Bu işlemi yapmak için yetkiniz yok!', Response::HTTP_FORBIDDEN);
        }
    }

    public function index()
    {
        $services = [];
        if (server()->isLinux()) {
            $raw = Command::runSudo(
                "systemctl list-units --all | grep service | awk '{print $1 \":\"$2\" \"$3\" \"$4\":\"$5\" \"$6\" \"$7\" \"$8\" \"$9\" \"$10}'",
                false
            );
            foreach (explode("\n", $raw) as &$package) {
                if ($package == '') {
                    continue;
                }
                if (str_contains($package, '●')) {
                    $package = explode('●:', $package)[1];
                }
                $row = explode(':', trim($package));
                try {
                    if (str_contains($row[0], "sysusers.service")) {
                        continue;
                    }

                    $status = explode(" ", $row[1]);
                    array_push($services, [
                        'name' => strlen($row[0]) > 50 ? substr($row[0], 0, 50) . '...' : $row[0],
                        'description' => strlen($row[2]) > 60 ? substr($row[2], 0, 60) . '...' : $row[2],
                        'status' => [
                            "loaded" => $status[0] == "loaded" ? true : false,
                            "active" => $status[1] == "active" ? true : false,
                            "running" => $status[2]
                        ],
                    ]);
                } catch (Exception) {
                }
            }
        } else {
            $rawServices = Command::run(
                "(Get-WmiObject win32_service | select Name, DisplayName, State, StartMode) -replace '\s\s+',':'"
            );
            $services = [];
            foreach (explode('}', $rawServices) as $service) {
                $row = explode(';', substr($service, 2));
                if ($row[0] == '') {
                    continue;
                }
                try {
                    array_push($services, [
                        'name' => trim(explode('=', $row[0])[1]),
                        'description' => trim(explode('=', $row[1])[1]),
                        'status' => trim(explode('=', $row[2])[1]),
                    ]);
                } catch (Exception) {
                }
            }
        }

        return response()->json($services);
    }

    public function start(Request $request)
    {
        $request->validate([
            'services' => 'required'
        ]);

        $services = request('services');
        if (server()->isLinux()) {
            foreach ($services as $service) {
                Command::runSudo("systemctl start @{:service}", [
                    'service' => $service
                ]);
            }
        } else {
            foreach ($services as $service) {
                Command::run("net start @{:service}", [
                    'service' => $service
                ]);
            }
        }

        return response()->json([
            'status' => true
        ]);
    }

    public function stop(Request $request)
    {
        $request->validate([
            'services' => 'required'
        ]);

        $services = request('services');
        if (server()->isLinux()) {
            foreach ($services as $service) {
                Command::runSudo("systemctl stop @{:service}", [
                    'service' => $service
                ]);
            }
        } else {
            foreach ($services as $service) {
                Command::run("net stop @{:service}", [
                    'service' => $service
                ]);
            }
        }

        return response()->json([
            'status' => true
        ]);
    }

    public function restart(Request $request)
    {
        $request->validate([
            'services' => 'required'
        ]);

        $services = request('services');
        if (server()->isLinux()) {
            foreach ($services as $service) {
                Command::runSudo("systemctl restart @{:service}", [
                    'service' => $service
                ]);
            }
        } else {
            foreach ($services as $service) {
                Command::run("net stop @{:service}", [
                    'service' => $service
                ]);
                Command::run("net start @{:service}", [
                    'service' => $service
                ]);
            }
        }

        return response()->json([
            'status' => true
        ]);
    }

    public function enable(Request $request)
    {
        $request->validate([
            'services' => 'required'
        ]);

        $services = request('services');
        if (server()->isLinux()) {
            foreach ($services as $service) {
                Command::runSudo("systemctl enable @{:service}", [
                    'service' => $service
                ]);
            }
        } else {
            foreach ($services as $service) {
                Command::run("sc config @{:service} start=auto", [
                    'service' => $service
                ]);
            }
        }

        return response()->json([
            'status' => true
        ]);
    }

    public function disable(Request $request)
    {
        $request->validate([
            'services' => 'required'
        ]);

        $services = request('services');
        if (server()->isLinux()) {
            foreach ($services as $service) {
                Command::runSudo("systemctl disable @{:service}", [
                    'service' => $service
                ]);
            }
        } else {
            foreach ($services as $service) {
                Command::run("sc config @{:service} start=demand", [
                    'service' => $service
                ]);
            }
        }

        return response()->json([
            'status' => true
        ]);
    }

    public function status(Request $request)
    {
        $request->validate([
            'service_name' => 'required'
        ]);

        $service = request('service_name');
        if (server()->isLinux()) {
            return response()->json(Command::runSudo("systemctl status @{:service}", [
                'service' => $service
            ]));
        }

        return response()->json(Command::run("sc query @{:service}", [
            'service' => $service
        ]));
    }
}
