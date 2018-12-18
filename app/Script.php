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
        $script = Script::fillValues($script,$rows);
        if(Script::where('unique_code',$script->unique_code)->exists()){
            return false;
        };
        $script->save();
        return $script;
    }

    public static function createFile($script,...$parameters){
        $script->save();;
        $file = fopen(storage_path('app' . DIRECTORY_SEPARATOR . 'scripts' ) . DIRECTORY_SEPARATOR . $script->_id, 'w');
        $user_inputs = array_slice($parameters[0],0,-1);
        foreach ($user_inputs as $parameter){
            fwrite($file,'#' . $parameter . PHP_EOL);
        }
        fwrite($file,$parameters[0][count($parameters[0]) -1]);
        fclose($file);
        return Script::fillValues($script,$user_inputs);
    }

    public static function fillValues($script, ... $parameters){
        $parameters = $parameters[0];
        for($i = 0 ; $i <= 12;$i++){
            $parameters[$i] = str_replace("# ","",$parameters[$i]);
            switch ($i){
                case 0:
                    $script->language = $parameters[$i];
                    break;
                case 1:
                    $script->encoding = $parameters[$i];
                    break;
                case 2:
                    $script->root = $parameters[$i];
                    break;
                case 3:
                    $script->name = $parameters[$i];
                    break;
                case 4:
                    $script->description = $parameters[$i];
                    break;
                case 5:
                    $script->version = $parameters[$i];
                    break;
                case 6:
                    $script->extensions = $parameters[$i];
                    break;
                case 7:
                    $script->inputs = $parameters[$i];
                    break;
                case 8:
                    $script->type = $parameters[$i];
                    break;
                case 9:
                    $script->authors = $parameters[$i];
                    break;
                case 10:
                    $script->support_email = $parameters[$i];
                    break;
                case 11:
                    $script->company = $parameters[$i];
                    break;
                case 12:
                    $script->unique_code = $parameters[$i];
                    break;
                case 13:
                    $script->regex = $parameters[$i];
                    break;
            }
        }
        return $script;
    }
}
