<?php

namespace app\Classes\Sandbox;

use Illuminate\Support\Facades\Blade;

class Validator
{
    public static function do($file){
        $string = file_get_contents($file);
        $test = Blade::compileString($string);
        preg_match_all("/(?<=\<\?php)((.|\n)*)(?=\?\>)/U",$test,$insidePhp);
        $results = [];
        foreach($insidePhp[0] as $php){
            preg_match_all("/\w*(?=\()/",$php,$duygu);
            array_push($results,$duygu[0]);
        }
        return $results;
    }
}