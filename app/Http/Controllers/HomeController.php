<?php

namespace App\Http\Controllers;

use App\Models\Extension;
use App\Models\LimanRequest;
use App\Models\Server;
use App\Models\Token;
use App\System\Command;
use App\User;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

/**
 * Home Controller
 */
class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Creates Liman dashboard
     *
     * @return JsonResponse|Response
     */
    public function index()
    {
        system_log(7, 'HOMEPAGE');

        return magicView('index', [
            'token' => Token::create(user()->id),
            'server_count' => Server::all()->count(),
            'extension_count' => Extension::all()->count(),
            'user_count' => User::all()->count(),
            'version' => getVersion() . ' - ' . getVersionCode(),
        ]);
    }


    /**
     * Returns ticket requests page
     *
     * @return JsonResponse|Response
     */
    public function all()
    {
        $requests = LimanRequest::where('user_id', auth()->id())->get();
        foreach ($requests as $request) {
            $request->status = match ($request->status) {
                '0' => __('Talep Alındı'),
                '1' => __('İşleniyor'),
                '2' => __('Tamamlandı.'),
                '3' => __('Reddedildi.'),
                default => __('Bilinmeyen.'),
            };
        }

        return magicView('permission.all', [
            'requests' => $requests,
        ]);
    }


    /**
     * Get liman server stats
     *
     * @return Application|ResponseFactory|Response
     */
    public function getLimanStats()
    {
        $cores = str_replace("cpu cores\t: ", "", trim(explode("\n", Command::runLiman("cat /proc/cpuinfo | grep 'cpu cores'"))[0]));
        $cpuUsage = shell_exec(
            "ps -eo %cpu --no-headers | grep -v 0.0 | awk '{s+=$1} END {print s/NR*10}'"
        );
        $cpuUsage = round($cpuUsage, 2);
        $ramUsage = shell_exec("free -t | awk 'NR == 2 {printf($3/$2*100)}'");
        $ramUsage = round($ramUsage, 2);
        $ioPercent = (float) shell_exec(
            "iostat -d | tail -n +4 | head -n -1 | awk '{s+=$2} END {print s}'"
        );

        $firstDown = $this->calculateNetworkBytes();
        $firstUp = $this->calculateNetworkBytes(false);
        sleep(1);
        $secondDown = $this->calculateNetworkBytes();
        $secondUp = $this->calculateNetworkBytes(false);

        $totalCpu = round($cpuUsage / $cores, 2);

        return response([
            'cpu' => $totalCpu > 100 ? 100 : $totalCpu,
            'ram' => $ramUsage > 100 ? 100 : $ramUsage,
            'io' => $ioPercent > 100 ? 100 : round($ioPercent, 2),
            'network' => [
                'download' => round(($secondDown - $firstDown) / 1024 / 2, 2),
                'upload' => round(($secondUp - $firstUp) / 1024 / 2, 2),
            ]
        ]);
    }

    /**
     * Calculates network bytes
     *
     * @param $download
     * @return int|string
     */
    private function calculateNetworkBytes($download = true)
    {
        $text = $download ? 'rx_bytes' : 'tx_bytes';
        if ($text == 'rx_bytes' || $text == 'tx_bytes') {
            $count = 0;
            $raw = trim(shell_exec("cat /sys/class/net/*/statistics/$text"));

            foreach (explode("\n", trim($raw)) as $data) {
                $count += intval($data);
            }

            return $count;
        } else {
            return 'Invalid data';
        }
    }

    /**
     * Sets locale for user
     *
     * @return Application|ResponseFactory|RedirectResponse|Response
     */
    public function setLocale()
    {
        system_log(7, 'SET_LOCALE');
        $languages = getLanguages();
        if (
            request()->has('locale') &&
            in_array(request('locale'), $languages)
        ) {
            \Session::put('locale', request('locale'));
            auth()->user()->update([
                'locale' => request('locale'),
            ]);

            return redirect()->back();
        } else {
            return response('Language not found', 404);
        }
    }

    /**
     * Get server uptime status
     *
     * @param $count
     * @return array
     */
    public function getServerStatus($count = 6)
    {
        $servers = Server::orderBy('updated_at', 'DESC')->limit($count)->get();
        $data = [];

        foreach ($servers as $server) {
            $status = @fsockopen(
                $server->ip_address,
                $server->control_port,
                $errno,
                $errstr,
                1);

            try {
                if ($status) {
                    if ($server->isWindows() && $server->canRunCommand()) {
                        preg_match('/\d+/', (string) $server->getUptime(), $output);
                        $uptime = $output[0];
                    } elseif ($server->canRunCommand()) {
                        $uptime = $server->getUptime();
                    } else {
                        $uptime = '';
                    }

                    if ($uptime != '') {
                        $uptime = \Carbon\Carbon::parse($uptime)->diffForHumans();
                    }
                } else {
                    $uptime = ' ';
                }
            } catch (\Throwable) {
                $uptime = ' ';
            }

            array_push($data, [
                'id' => $server->id,
                'icon' => $server->isLinux() ? 'fa-linux' : 'fa-windows',
                'name' => $server->name,
                'uptime' => (bool) $uptime ? $uptime : null,
                'badge_class' => (bool) $status ? 'badge-success' : 'badge-danger',
                'status' => (bool) $status,
            ]);
        }

        return $data;
    }
}
