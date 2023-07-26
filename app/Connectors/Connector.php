<?php

namespace App\Connectors;

use App\Models\Server;

/**
 * Connector Interface
 */
interface Connector
{
    /**
     * Construct Connector
     *
     * @param Server $server
     * @param $user_id
     */
    public function __construct(Server $server, $user_id);

    /**
     * Verify function
     *
     * @param $ip_address
     * @param $username
     * @param $password
     * @param $port
     * @return string
     */
    public static function verify($ip_address, $username, $password, $port);

    /**
     * @param Server $server
     * @param $username
     * @param $password
     * @param $user_id
     * @param $key
     * @param $port
     * @return mixed
     */
    public static function create(
        Server $server,
                           $username,
                           $password,
                           $user_id,
                           $key,
                           $port = null
    );

    /**
     * Execute command on connected server
     *
     * @param $command
     * @return mixed
     */
    public function execute($command);

    /**
     * Send file to connected server
     *
     * @param $localPath
     * @param $remotePath
     * @param $permissions
     * @return mixed
     */
    public function sendFile($localPath, $remotePath, $permissions = 0644);

    /**
     * Receive file from connected server
     *
     * @param $localPath
     * @param $remotePath
     * @return mixed
     */
    public function receiveFile($localPath, $remotePath);
}
