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
        $services = $server->extensions;
        $available_services = Extension::all();
        return view('server.one_auth', [
            "stats" => \request('server')->run("df -h"),
            "hostname" => request('server')->run("hostname"),
            "server" => \request('server'),
            "available_services" => $available_services,
            "services" => $services,
        ]);
    }

    public function unauthorized(){
        $services = request('server')->extensions;
        return view('server.one',[
            "services" => $services,
            "server" => request('server')
        ]);
    }
}
