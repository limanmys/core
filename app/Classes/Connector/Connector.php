<?php

namespace App\Classes\Connector;

interface Connector
{
    public function __construct(\App\Server $server);

    public function __destruct();

    public function execute($command);

    public function sendFile($localPath, $remotePath, $permissions = 0644);

    public function receiveFile($localPath, $remotePath);

    public static function create(\App\Server $server, $username, $password, $user_id);
}