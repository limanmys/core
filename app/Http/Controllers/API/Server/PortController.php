<?php

namespace App\Http\Controllers\API\Server;

use App\Exceptions\JsonResponseException;
use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\System\Command;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * Server Port Controller
 */
class PortController extends Controller
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
     * Get open ports on server
     *
     * @return JsonResponse|Response
     *
     * @throws GuzzleException
     * @throws GuzzleException
     */
    public function index()
    {
        if (server()->os != 'linux') {
            return respond('Bu sunucuda portları kontrol edemezsiniz!', Response::HTTP_FORBIDDEN);
        }

        $output = trim(
            Command::runSudo(
                "lsof -i -P -n | grep -v '\-'| awk -F' ' '{print $1,$3,$5,$8,$9}' | sed 1,1d"
            )
        );

        if (empty($output)) {
            return response()->json([]);
        }

        $arr = [];
        foreach (explode("\n", $output) as $line) {
            $row = explode(' ', $line);
            $arr[] = [
                'name' => $row[0],
                'username' => $row[1],
                'ip_type' => $row[2],
                'packet_type' => $row[3],
                'port' => $row[4],
            ];
        }

        return response()->json($arr);
    }
}
