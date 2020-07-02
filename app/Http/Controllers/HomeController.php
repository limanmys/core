<?php

namespace App\Http\Controllers;

use App\Models\LimanRequest;
use App\Models\Server;
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
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
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

        return view('index', [
            "widgets" => $widgets,
        ]);
    }

    public function getLimanStats()
    {
        $cpuUsage = shell_exec(
            "grep 'cpu ' /proc/stat | awk '{usage=($2+$4)*100/($2+$4+$5)} END {print \"%\" usage}'"
        );
        $cpuUsage = substr($cpuUsage, 0, 5);
        $ramUsage = shell_exec("free -t | awk 'NR == 2 {printf($3/$2*100)}'");
        $ramUsage = substr($ramUsage, 0, 5);
        $disk = shell_exec('df -h / | grep /');
        preg_match("/(\d+)%/", $disk, $diskUsage);

        $firstDown = self::calculateNetworkBytes();
        $firstUp = self::calculateNetworkBytes(false);
        sleep(1);
        $secondDown = self::calculateNetworkBytes();
        $secondUp = self::calculateNetworkBytes(false);

        $network =
            strval(intval(($secondDown - $firstDown) / 1024 / 1024) / 2) .
            " mb/sn ↓  " .
            strval(intval(($secondUp - $firstUp) / 1024 / 1024) / 2) .
            " mb/sn ↑";
        return response([
            "cpu" => $cpuUsage,
            "ram" => "%" . $ramUsage,
            "disk" => "%" . $diskUsage[1],
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

    public function new()
    {
        return view('permission.request');
    }

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
        return view('permission.all', [
            "requests" => $requests,
        ]);
    }

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
