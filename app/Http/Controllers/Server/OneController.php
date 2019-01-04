<?php

namespace App\Http\Controllers\Server;

use App\Extension;
use App\Http\Controllers\Controller;

class OneController extends Controller
{
    public function main(){
        return (request('server')->type == "linux_ssh" || request('server')->type == "windows_powershell")
            ? $this->authorized() : $this->unauthorized();
    }

    public function authorized(){
        $server = \request('server');
        $available_services = Extension::all();
        $services = [];
        foreach ($server->extensions as $service){
            array_push($services,$available_services->where('_id',$service)->first());
        }
        return view('server.one_auth', [
            "stats" => \request('server')->run("df -h"),
            "hostname" => request('server')->run("hostname"),
            "server" => \request('server'),
            "available_services" => $available_services,
            "services" => $services,
        ]);
    }

    public function unauthorized(){
        $available_services = Extension::all();
        $services = [];
        foreach (request('server')->extensions as $service){
            array_push($services,$available_services->where('_id',$service)->first());
        }
        return view('server.one',[
            "services" => $services,
            "server" => request('server')
        ]);
    }
}
