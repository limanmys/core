<?php

namespace App\Http\Controllers\API\Server;

use App\Exceptions\JsonResponseException;
use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\System\Command;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * Package Controller
 */
class PackageController extends Controller
{
    public function __construct()
    {
        if (! Permission::can(auth('api')->user()->id, 'liman', 'id', 'server_details')) {
            throw new JsonResponseException([
                'message' => 'Bu işlemi yapmak için yetkiniz yok!'
            ], '', Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * List packages that is installed on system
     *
     * @return JsonResponse
     * @throws GuzzleException
     */
    public function index()
    {
        $pkgman = Command::runSudo(
            'which apt >/dev/null 2>&1 && echo apt || echo rpm'
        );

        if ($pkgman == 'apt') {
            $raw = Command::runSudo(
                "apt list --installed 2>/dev/null | sed '1,1d'"
            );
        } else {
            $raw = Command::runSudo(
                "yum list --installed 2>/dev/null | awk {'print $1 \" \" $2 \" \"  $3'} | sed '1,1d'"
            );
        }

        $packages = [];
        foreach (explode("\n", $raw) as $package) {
            if ($package == '') {
                continue;
            }
            $row = explode(' ', $package);
            try {
                $packages[] = [
                    'name' => $row[0],
                    'version' => $row[1],
                    'type' => $row[2],
                ];
            } catch (Exception) {
            }
        }

        return response()->json($packages);
    }
}
