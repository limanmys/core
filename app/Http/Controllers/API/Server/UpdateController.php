<?php

namespace App\Http\Controllers\API\Server;

use App\Http\Controllers\Controller;
use App\System\Command;

class UpdateController extends Controller
{
    public function index()
    {
        $pkgman = Command::runSudo(
            'which apt >/dev/null 2>&1 && echo apt || echo rpm'
        );

        if ($pkgman == 'apt') {
            $updates = [];
            $raw = Command::runSudo(
                'apt-get -qq update 2> /dev/null > /dev/null; '.
                "apt list --upgradable 2>/dev/null | sed '1,1d'"
            );
            foreach (explode("\n", $raw) as $package) {
                if ($package == '' || str_contains($package, 'List')) {
                    continue;
                }
                $row = explode(' ', $package, 4);
                try {
                    array_push($updates, [
                        'name' => $row[0],
                        'version' => $row[1],
                        'type' => $row[2],
                        'status' => $row[3],
                    ]);
                } catch (\Exception) {
                }
            }

            return response()->json($updates);
        }

        if ($pkgman == 'rpm') {
            $updates = [];
            $raw = Command::runSudo(
                "yum list upgrades --exclude=*.src 2>/dev/null | awk {'print $1 \" \" $2 \" \" $3'} | sed '1,3d'"
            );
            foreach (explode("\n", $raw) as $package) {
                if ($package == '' || str_contains($package, 'List')) {
                    continue;
                }
                $row = explode(' ', $package, 4);
                try {
                    array_push($updates, [
                        'name' => $row[0],
                        'version' => $row[1],
                        'type' => $row[2],
                    ]);
                } catch (\Exception) {
                }
            }

            return response()->json($updates);
        }
    }
}
