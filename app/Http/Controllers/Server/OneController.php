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
        return view('server.one_auth', [
            "stats" => \request('server')->run("df -h"),
            "hostname" => request('server')->run("hostname"),
            "server" => \request('server'),
            "installed_extensions" => $this->installedExtensions(),
            "available_extensions" => $this->availableExtensions(),
        ]);
    }

    public function unauthorized(){
        return view('server.one',[
            "installed_extensions" => $this->installedExtensions(),
            "available_extensions" => $this->availableExtensions(),
            "server" => request('server')
        ]);
    }

    public function availableExtensions(){
        return Extension::whereNotIn('_id',request('server')->extensions)->get();
    }

    public function installedExtensions(){
        return Extension::whereIn('_id',request('server')->extensions)->get();
    }
}
