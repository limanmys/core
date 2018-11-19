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
        for($i = 0 ; $i <= 12;$i++){
            $rows[$i] = str_replace("#","",$rows[$i]);
            switch ($i){
                case 0:
                    $script->language = $rows[$i];
                    break;
                case 1:
                    $script->root = $rows[$i];
                    break;
                case 2:
                    $script->name = $rows[$i];
                    break;
                case 3:
                    $script->description = $rows[$i];
                    break;
                case 4:
                    $script->version = $rows[$i];
                    break;
                case 5:
                    $script->features = $rows[$i];
                    break;
                case 6:
                    $script->inputs = $rows[$i];
                    break;
                case 7:
                    $script->outputs = $rows[$i];
                    break;
                case 8:
                    $script->type = $rows[$i];
                    break;
                case 9:
                    $script->authors = $rows[$i];
                    break;
                case 10:
                    $script->support_email = $rows[$i];
                    break;
                case 11:
                    $script->company = $rows[$i];
                    break;
            }
        }
        $script->save();
        return $script;
    }
}
