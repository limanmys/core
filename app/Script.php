<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class Script extends Eloquent
{
    protected $collection = 'scripts';
    protected $connection = 'mongodb';

    public static function readFromFile($uploadedFile){
        $file = file_get_contents($uploadedFile);
        $rows = explode("\n", $file);
        $script = new Script();
        for($i = 0 ; $i <= 13;$i++){
            $rows[$i] = str_replace("#","",$rows[$i]);
            switch ($i){
                case 0:
                    $script->language = $rows[$i];
                    break;
                case 1:
                    $script->encoding = $rows[$i];
                    break;
                case 2:
                    $script->root = $rows[$i];
                    break;
                case 3:
                    $script->name = $rows[$i];
                    break;
                case 4:
                    $script->description = $rows[$i];
                    break;
                case 5:
                    $script->version = $rows[$i];
                    break;
                case 6:
                    $rows[$i] = explode(',',$rows[$i]);
                    $script->features = $rows[$i];
                    break;
                case 7:
                    $script->inputs = $rows[$i];
                    break;
                case 8:
                    $script->outputs = $rows[$i];
                    break;
                case 9:
                    $script->type = $rows[$i];
                    break;
                case 10:
                    $script->authors = $rows[$i];
                    break;
                case 11:
                    $script->support_email = $rows[$i];
                    break;
                case 12:
                    $script->company = $rows[$i];
                    break;
                case 13:
                    $script->unique_code = $rows[$i];
                    break;
            }
        }
        $script->save();
        return $script;
    }
}
