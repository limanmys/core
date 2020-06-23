<?php

namespace App\Classes\Sandbox;

interface Sandbox
{
    public function __construct(
        $server = null,
        $extension = null,
        $user = null,
        $request = null
    );

    public function getPath();

    public function getFileExtension();

    public function command($function, $extensiondb = null);

    public function getInitialFiles();

    public function setLogId($logId);
}
