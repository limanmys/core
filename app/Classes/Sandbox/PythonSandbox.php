<?php

namespace App\Classes\Sandbox;

class PythonSandbox implements Sandbox{
    private $path = "/liman/sandbox/python/index.py";
    private $fileExtension = ".blade.php";

    public function getPath(){
        return $this->path;
    }

    public function getFileExtension(){
        return $this->fileExtension;
    }

    public function command(){
        return "";
    }

    public function getInitialFiles(){
        return [
            "index.html.jinja" , "functions.php"
        ];
    }
}