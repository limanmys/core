<?php

namespace App\Http\Controllers\API\Server;

use App\Exceptions\JsonResponseException;
use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Server;
use App\System\Command;
use Carbon\Carbon;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Server Details Controller
 */
class DetailsController extends Controller
{
    /**
     * List servers that user can access
     *
     * @return JsonResponse
     */
    public function index()
    {
        return Server::orderBy('updated_at', 'DESC')
            ->get()
            ->filter(function ($server) {
                return Permission::can(auth('api')->user()->id, 'server', 'id', $server->id);
            })
            ->map(function ($server) {
                $server->extension_count = $server->extensions()->filter(function ($extension) {
                    return Permission::can(auth('api')->user()->id, 'extension', 'id', $extension->id);
                })->count();

                return $server;
            })
            ->values();
    }

    /**
     * Get single server detail
     *
     * @return JsonResponse|Response
     * @throws GuzzleException
     */
    public function server()
    {
        $server = server();
        if (! $server) {
            throw new JsonResponseException([
                'message' => 'Sunucu bulunamadı.'
            ], Response::HTTP_NOT_FOUND);
        }

        if (! Permission::can(auth('api')->user()->id, 'liman', 'id', 'server_details')) {
            throw new JsonResponseException([
                'message' => 'Bu işlemi yapmak için yetkiniz yok!'
            ], '', Response::HTTP_FORBIDDEN);
        }

        try {
            if ($server->isWindows()) {
                preg_match('/\d+/', (string) $server->getUptime(), $output);
                $uptime = $output[0];
            } else {
                $uptime = $server->getUptime();
            }
            $uptime = Carbon::parse($uptime)->diffForHumans();
        } catch (\Throwable) {
            $uptime = __('Uptime parse edemiyorum.');
        }

        $outputs = [
            'hostname' => $server->getHostname(),
            'os' => $server->getVersion(),
            'services' => $server->getNoOfServices(),
            'processes' => $server->getNoOfProcesses(),
            'uptime' => $uptime,
        ];

        if ($server->canRunCommand()) {
            $outputs['user'] = Command::run('whoami');
        }

        $server['is_favorite'] = $server->isFavorite();

        return response()->json([
            'server' => $server,
            'details' => $outputs,
        ]);
    }

    /**
     * Get stats of server for graphs
     *
     * @return array
     * @throws GuzzleException
     */
    public function stats()
    {
        if (! Permission::can(auth('api')->user()->id, 'liman', 'id', 'server_details')) {
            throw new JsonResponseException([
                'message' => 'Bu işlemi yapmak için yetkiniz yok!'
            ], '', Response::HTTP_FORBIDDEN);
        }

        if (server()->isLinux()) {
            $cpuPercent = Command::runSudo(
                "ps -eo %cpu --no-headers | grep -v 0.0 | awk '{s+=$1} END {print s/NR*10}'"
            );
            $ramPercent = Command::runSudo(
                "free | grep Mem | awk '{print $3/$2 * 100.0}'"
            );
            $ioPercent = (float) Command::runSudo(
                "iostat -d | tail -n +4 | head -n -1 | awk '{s+=$2} END {print s}'"
            );
            $firstDown = $this->calculateNetworkBytes();
            $firstUp = $this->calculateNetworkBytes(false);
            sleep(1);
            $secondDown = $this->calculateNetworkBytes();
            $secondUp = $this->calculateNetworkBytes(false);

            return [
                'cpu' => round((float) $cpuPercent, 2),
                'ram' => round((float) $ramPercent, 2),
                'io' => round((float) $ioPercent, 2),
                'network' => [
                    'download' => round(($secondDown - $firstDown) / 1024 / 2, 2),
                    'upload' => round(($secondUp - $firstUp) / 1024 / 2, 2),
                ],
            ];
        }

        return [
            'disk' => 0,
            'ram' => 0,
            'cpu' => 0,
            'network' => [
                'download' => 0,
                'upload' => 0,
            ],
        ];
    }

    /**
     * Get server specs
     *
     * @return JsonResponse
     * @throws GuzzleException
     */
    public function specs()
    {
        if (! Permission::can(auth('api')->user()->id, 'liman', 'id', 'server_details')) {
            throw new JsonResponseException([
                'message' => 'Bu işlemi yapmak için yetkiniz yok!'
            ], '', Response::HTTP_FORBIDDEN);
        }

        $cores = str_replace("cpu cores\t: ", '', trim(explode("\n", Command::runSudo("cat /proc/cpuinfo | grep 'cpu cores'"))[0]));
        $cpu = str_replace("model name\t: ", '', trim(explode("\n", Command::runSudo("cat /proc/cpuinfo | grep 'model name'"))[0]));
        $ram = Command::runSudo("dmidecode -t memory | grep 'Size' | awk '{print $2}' | paste -sd+ | bc");
        if ($ram > 1000) {
            $ram = round($ram / 1000, 0).' GB';
        } else {
            $ram = $ram.' GB';
        }
        $model = Command::runSudo('dmidecode -s system-product-name');
        $manufacturer = Command::runSudo('dmidecode -s system-manufacturer');

        return response()->json([
            'cpu' => $cores.'x '.$cpu,
            'ram' => $ram,
            'model' => $model,
            'manufacturer' => $manufacturer,
        ]);
    }

    /**
     * Top CPU using processes
     *
     * @return Application|Factory|View
     *
     * @throws GuzzleException
     * @throws GuzzleException
     */
    public function topCpuProcesses()
    {
        if (! Permission::can(auth('api')->user()->id, 'liman', 'id', 'server_details')) {
            throw new JsonResponseException([
                'message' => 'Bu işlemi yapmak için yetkiniz yok!'
            ], '', Response::HTTP_FORBIDDEN);
        }

        $output = trim(
            Command::runSudo(
                "ps -eo pid,%cpu,user,cmd --sort=-%cpu --no-headers | head -n 5 | awk '{print $1\"*-*\"$2\"*-*\"$3\"*-*\"$4}'"
            )
        );

        return response()->json($this->parsePsOutput($output));
    }

    /**
     * Get top memory processes
     *
     * @return Application|Factory|View
     *
     * @throws GuzzleException
     * @throws GuzzleException
     */
    public function topMemoryProcesses()
    {
        if (! Permission::can(auth('api')->user()->id, 'liman', 'id', 'server_details')) {
            throw new JsonResponseException([
                'message' => 'Bu işlemi yapmak için yetkiniz yok!'
            ], '', Response::HTTP_FORBIDDEN);
        }

        $output = trim(
            Command::runSudo(
                "ps -eo pid,%mem,user,cmd --sort=-%mem --no-headers | head -n 5 | awk '{print $1\"*-*\"$2\"*-*\"$3\"*-*\"$4}'"
            )
        );

        return response()->json($this->parsePsOutput($output));
    }

    /**
     * Top disk usage
     *
     * @return Application|Factory|View
     *
     * @throws GuzzleException
     * @throws GuzzleException
     */
    public function topDiskUsage()
    {
        if (! Permission::can(auth('api')->user()->id, 'liman', 'id', 'server_details')) {
            throw new JsonResponseException([
                'message' => 'Bu işlemi yapmak için yetkiniz yok!'
            ], '', Response::HTTP_FORBIDDEN);
        }

        $output = trim(
            Command::runSudo(
                "df --output=pcent,source,size,used -hl -x squashfs -x tmpfs -x devtmpfs | sed -n '1!p' | head -n 5 | sort -hr | awk '{print $1\"*-*\"$2\"*-*\"$3\"*-*\"$4}'"
            )
        );

        return response()->json($this->parseDfOutput($output));
    }

    /**
     * Add server to favorites
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function favorite(Request $request)
    {
        auth('api')->user()
            ->myFavorites()
            ->toggle($request->server_id);

        return response()->json([
            'message' => 'İşlem başarılı.'
        ]);
    }

    /**
     * Calculate network flow as bytes
     *
     * @return int
     *
     * @throws GuzzleException
     */
    private function calculateNetworkBytes($download = true)
    {
        $text = $download ? 'rx_bytes' : 'tx_bytes';
        $count = 0;
        $raw = Command::runSudo('cat /sys/class/net/*/statistics/:text:', [
            'text' => $text,
        ]);
        foreach (explode("\n", trim((string) $raw)) as $data) {
            $count += intval($data);
        }

        return $count;
    }

    /**
     * Parse ps-aux output
     *
     * @return array
     */
    private function parsePsOutput($output)
    {
        $data = [];
        foreach (explode("\n", (string) $output) as $row) {
            $row = explode('*-*', $row);
            $row[3] = str_replace('\\', '/', $row[3]);
            $fetch = explode('/', $row[3]);
            $data[] = [
                'pid' => $row[0],
                'percent' => $row[1],
                'user' => $row[2],
                'cmd' => end($fetch),
            ];
        }

        return $data;
    }

    /**
     * Parse df-h output
     *
     * @return array
     */
    private function parseDfOutput($output)
    {
        $data = [];
        foreach (explode("\n", (string) $output) as $row) {
            $row = explode('*-*', $row);
            $row[1] = str_replace('\\', '/', $row[1]);
            $fetch = explode('/', $row[1]);
            $data[] = [
                'percent' => str_replace('%', '', $row[0]),
                'source' => end($fetch),
                'size' => $row[2],
                'used' => $row[3],
            ];
        }

        return $data;
    }
}
