<?php

namespace App\Classes\Sandbox;

interface Sandbox
{
    public function getPath();

    public function getFileExtension();

    public function command($function, $extensiondb = null);

    public function getInitialFiles();
}
