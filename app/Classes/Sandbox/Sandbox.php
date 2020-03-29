<?php

namespace App\Classes\Sandbox;

interface Sandbox
{
    public function getPath();

    public function getFileExtension();

    public function command($function);

    public function getInitialFiles();
}