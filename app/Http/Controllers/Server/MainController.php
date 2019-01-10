<?php

namespace App\Http\Controllers\Server;

use App\Server;
use App\Http\Controllers\Controller;

class MainController extends Controller
{
    public function all()
    {
        // Retrieve all servers.
        $servers = Server::getAll();

        return view('server.index', [
            "servers" => $servers
        ]);
    }

    public function isAlive()
    {
        $output = shell_exec("echo exit | telnet " . \request('ip') . " " . \request('port'));
        if (!strpos($output, "Connected to " . \request('ip'))) {
            return [
                "result" => 201,
                "data" => $output
            ];
        } else {
            return [
                "result" => 200,
                "data" => $output
            ];
        }
    }
}
