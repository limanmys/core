<?php

namespace App\Sandboxes;

/**
 * DEPRECATED
 */
interface Sandbox
{
    /**
     * @param $server
     * @param $extension
     * @param $user
     * @param $request
     */
    public function __construct(
        $server = null,
        $extension = null,
        $user = null,
        $request = null
    );

    /**
     * @return mixed
     */
    public function getPath();

    /**
     * @return mixed
     */
    public function getFileExtension();

    /**
     * @param $function
     * @param $extensiondb
     * @return mixed
     */
    public function command($function, $extensiondb = null);

    /**
     * @return mixed
     */
    public function getInitialFiles();

    /**
     * @param $logId
     * @return mixed
     */
    public function setLogId($logId);
}
