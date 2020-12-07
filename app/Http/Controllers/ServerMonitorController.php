<?php

namespace App\Http\Controllers;

use App\Http\Middleware\Server;
use Illuminate\Http\Request;
use App\Models\MonitorServer;
use App\Models\UserMonitors;
use Carbon\Carbon;

class ServerMonitorController extends Controller
{
    public function add()
    {
        $obj = MonitorServer::where([
            'ip_address' => request('ip_address'),
            'port' => request('port')
        ])->first();
        if(!$obj){
            $status = checkPort(request('ip_address'),request('port'));
            $obj = new MonitorServer([
                'ip_address' => request('ip_address'),
                'port' => request('port'),
                'online' => $status,
                'last_checked' => Carbon::now()
            ]);
            $obj->save();
        }

        UserMonitors::create([
            "name" => request('name'),
            "server_monitor_id" => $obj->id,
            "user_id" => user()->id
        ]);

        return respond("Başarıyla eklendi!");
    }

    public function remove()
    {
        //Find Object.
        $obj = UserMonitors::find(request('server_monitor_id'));
        if(!$obj){
            return respond("Bu sunucu takibi bulunamadı!",201);
        }

        //Let's search if this is the only occurence of tracking.
        $monitors = UserMonitors::where('server_monitor_id',$obj->server_monitor_id)->get();
        if($monitors->count() == 1){
            MonitorServer::find($obj->server_monitor_id)->delete();
        }
        $obj->delete();

        return respond("Sunucu takibi başarıyla silindi!");
    }

    public function refresh()
    {
        $obj = UserMonitors::find(request('server_monitor_id'));
        if(!$obj){
            return respond("Bu sunucu takibi bulunamadı!",201);
        }

        $server = MonitorServer::find($obj->server_monitor_id);
        if(!$server){
            return respond("Bu sunucu takibi bulunamadı!",201);   
        }

        $status = checkPort($server->ip_address,$server->port);
        $server->update([
            "online" => $status,
            "last_checked" => Carbon::now()
        ]);
        return respond("Başarıyla yenilendi!");
    }

    public function list()
    {
        $servers = UserMonitors::where('user_id', user()->id)->get()->map(function($server){
            $obj = MonitorServer::find($server->server_monitor_id);
            if(!$obj){
                return $server;
            }
            $server->online = $obj->online;
            $server->last_checked = $obj->last_checked;
            $server->ip_address = $obj->ip_address;
            $server->port = $obj->port;
            return $server;
        });
        return magicView('monitor.index',[
            "monitor_servers" => $servers,
            "servers" => servers()
        ]);
    }

    public function get()
    {

    }
}
