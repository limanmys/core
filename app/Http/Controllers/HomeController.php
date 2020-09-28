<?php

namespace App\Http\Controllers;

use App\Models\LimanRequest;
use App\Models\Server;
use App\User;
use App\Models\Token;
use App\Models\UserSettings;
use App\Models\Extension;
use App\Models\Widget;

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
     * @api {get} / Homepage
     * @apiName HomePage
     * @apiDescription Liman' database stats.
     * @apiGroup General
     *
     * @apiSuccess {Integer} server_count Count of the servers in Liman.
     * @apiSuccess {Integer} extension_count Count of the extensions in Liman.
     * @apiSuccess {Integer} user_count Count of the users in Liman.
     * @apiSuccess {Integer} settings_count Count of the settings in Liman.
     */

    public function index()
    {
        system_log(7, "HOMEPAGE");
        $widgets = Widget::where('user_id', auth()->id())
            ->orderBy('order')
            ->get();
        foreach ($widgets as $widget) {
            $widget->server_name = Server::where(
                'id',
                $widget->server_id
            )->first()->name;
        }
        return magicView('index', [
            "token" => Token::create(user()->id),
            "widgets" => $widgets,
            "server_count" => Server::all()->count(),
            "extension_count" => Extension::all()->count(),
            "user_count" => User::all()->count(),
            "settings_count" => UserSettings::all()->count(),
        ]);
    }

    /**
     * @api {post} / Liman Stats
     * @apiName Liman Stats
     * @apiDescription Hardware stats of Liman.
     * @apiGroup General
     *
     * @apiSuccess {String} cpu CPU Usage of Liman.
     * @apiSuccess {String} ram Ram Usage of Liman.
     * @apiSuccess {String} disk Disk Usage of Liman.
     * @apiSuccess {String} network Network Usage of Liman.
     */
    public function getLimanStats()
    {
        $cpuUsage = shell_exec(
            "ps -eo %cpu --no-headers | grep -v 0.0 | awk '{s+=$1} END {print s/NR*10}'"
        );
        $cpuUsage = round($cpuUsage, 0, 2);
        $ramUsage = shell_exec("free -t | awk 'NR == 2 {printf($3/$2*100)}'");
        $ramUsage = round($ramUsage, 0, 2);
        $diskUsage = shell_exec("df --output=pcent / | tr -dc '0-9'");
        $diskUsage = round($diskUsage, 0, 2);

        $firstDown = $this->calculateNetworkBytes();
        $firstUp = $this->calculateNetworkBytes(false);
        sleep(1);
        $secondDown = $this->calculateNetworkBytes();
        $secondUp = $this->calculateNetworkBytes(false);

        $network =
            strval(intval(($secondDown - $firstDown) / 1024 / 1024) / 2) .
            " mb/sn ↓  " .
            strval(intval(($secondUp - $firstUp) / 1024 / 1024) / 2) .
            " mb/sn ↑";
        return response([
            "cpu" => "%" . $cpuUsage,
            "ram" => "%" . $ramUsage,
            "disk" => "%" . $diskUsage,
            "network" => $network,
        ]);
    }

    public function setLocale()
    {
        system_log(7, "SET_LOCALE");
        $languages = ["tr", "en"];
        if (
            request()->has('locale') &&
            in_array(request('locale'), $languages)
        ) {
            \Session::put('locale', request('locale'));
            return redirect()->back();
        } else {
            return response('Language not found', 404);
        }
    }

    private function calculateNetworkBytes($download = true)
    {
        $text = $download ? "rx_bytes" : "tx_bytes";
        $count = 0;
        $raw = trim(shell_exec("cat /sys/class/net/*/statistics/$text"));

        foreach (explode("\n", $raw) as $data) {
            $count += intval($data);
        }
        return $count;
    }

    public function collapse()
    {
        if (\Session::has('collapse')) {
            \Session::remove('collapse');
        } else {
            \Session::put('collapse', '');
        }
        return respond('Ok', 200);
    }

    public function setTheme()
    {
        if (\Session::has('dark_mode')) {
            \Session::remove('dark_mode');
        } else {
            \Session::put('dark_mode', 'true');
        }
        return respond('Tema Guncellendi.');
    }

    /**
     * @api {get} /taleplerim Personal Liman Requests List
     * @apiName Personal Liman Requests List
     * @apiGroup General
     *
     * @apiSuccess {Array} requests Array of request objects.
     */
    public function all()
    {
        $requests = LimanRequest::where('user_id', auth()->id())->get();
        foreach ($requests as $request) {
            switch ($request->status) {
                case "0":
                    $request->status = __("Talep Alındı");
                    break;
                case "1":
                    $request->status = __("İşleniyor");
                    break;
                case "2":
                    $request->status = __("Tamamlandı.");
                    break;
                case "3":
                    $request->status = __("Reddedildi.");
                    break;
                default:
                    $request->status = __("Bilinmeyen.");
                    break;
            }
        }
        return magicView('permission.all', [
            "requests" => $requests,
        ]);
    }

    /**
     * @api {post} /talep Send Personal Liman Request
     * @apiName Send Personal Liman Request
     * @apiGroup General
     *
     * @apiParam {String} note Note of the request
     * @apiParam {String} type server,extension,other
     * @apiParam {String} speed normal,urgent
     *
     */
    public function request()
    {
        LimanRequest::create([
            "user_id" => auth()->id(),
            "email" => auth()->user()->email,
            "note" => request('note'),
            "type" => request('type'),
            "speed" => request('speed'),
            "status" => 0,
        ]);
        return respond('Talebiniz başarıyla alındı.', 200);
    }
}
