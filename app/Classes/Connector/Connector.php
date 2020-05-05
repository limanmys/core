<?php

namespace App\Classes\Connector;

interface Connector
{
    public function __construct(\App\Server $server, $user_id);

    public function execute($command);

    public function sendFile($localPath, $remotePath, $permissions = 0644);

    public function receiveFile($localPath, $remotePath);

    public function runScript($script, $parameters, $runAsRoot = false);

    public static function verify($ip_address, $username, $password, $port);

    public static function create(
        \App\Server $server,
        $username,
        $password,
        $user_id,
        $key
    );
}
