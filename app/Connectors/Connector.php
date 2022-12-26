<?php

namespace App\Connectors;

interface Connector
{
    public function __construct(\App\Models\Server $server, $user_id);

    public function execute($command);

    public function sendFile($localPath, $remotePath, $permissions = 0644);

    public function receiveFile($localPath, $remotePath);

    public static function verify($ip_address, $username, $password, $port);

    public static function create(
        \App\Models\Server $server,
        $username,
        $password,
        $user_id,
        $key,
        $port = null
    );
}
