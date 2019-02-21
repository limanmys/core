<?php

namespace App\Classes;

use App\Exceptions\Key\Invalid;
use App\Exceptions\Key\NotFound;
use App\Exceptions\Server\NotAvailable;

/**
 * Class SSHConnector
 * @package App\Classes
 */
class SSHConnector
{
    /**
     * @var mixed
     */
    protected $connection;


    /**
     * SSHConnector constructor.
     * @param \App\Server $server
     * @throws NotFound
     * @throws \Throwable
     */
    public function __construct(\App\Server $server)
    {
        try {
            $object = throw_unless(\App\Key::where('server_id', $server->_id)->first(), new NotFound());
            $this->connection = throw_unless(ssh2_connect($server->ip_address, $server->port), new NotAvailable());
            throw_unless(ssh2_auth_pubkey_file($this->connection, $object->username,
                storage_path('keys') . DIRECTORY_SEPARATOR . auth()->id() . '.pub',
                storage_path('keys') . DIRECTORY_SEPARATOR . auth()->id()),
                new Invalid());
        }catch (NotAvailable $exception){
            abort(500);
        }catch (\Exception $exception){
            throw new NotFound();
        }
    }

    /**
     * SSHConnector destructor
     */
    public function __destruct()
    {
        ssh2_disconnect($this->connection);
    }

    /**
     * @param $command
     * @return string
     */
    public function execute($command)
    {
        $stream = ssh2_exec($this->connection, $command);
        stream_set_blocking($stream, true);
        $data = "";
        while($buf = fread($stream, 4096)){
            $data .= $buf;
        }
        return $data;
    }

    /**
     * @param $localPath
     * @param $remotePath
     * @param int $permissions
     * @return bool
     */
    public function sendFile($localPath, $remotePath, $permissions = 0644)
    {
        return ssh2_scp_send($this->connection, $localPath, $remotePath, $permissions);
    }

    /**
     * @param $localPath
     * @param $remotePath
     * @return bool
     */
    public function receiveFile($localPath, $remotePath)
    {
        return ssh2_scp_recv($this->connection, $localPath, $remotePath);
    }
}