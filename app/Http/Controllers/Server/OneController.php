<?php

namespace App\Http\Controllers\Server;

use App\Extension;
use App\Script;
use App\Http\Controllers\Controller;

class OneController extends Controller
{
    public function main(){

    }

    private function authorized(){
        $server = \request('server');
        $services = $server->extensions;
        $available_services = Extension::all();
        return view('server.one', [
            "stats" => \request('server')->run("df -h"),
            "hostname" => request('server')->run("hostname"),
            "server" => \request('server'),
            "available_services" => $available_services,
            "services" => $services,
        ]);
    }

    private function unauthorized(){

    }
}
