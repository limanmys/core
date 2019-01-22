<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class Script extends Eloquent
{
    protected $collection = 'scripts';
    protected $connection = 'mongodb';

    public static function readFromFile($uploadedFile){
        
        // Read values from text file.
        $file = file_get_contents($uploadedFile);

        // Seperate each rows to parse.
        $rows = explode("\n", $file);

        // Create new script object.
        $script = new Script();

        // Fill script values using parsed rows.
        $script = Script::fillValues($script,$rows);

        // Check if script already exists, if so simply don't resave it.
        if(Script::where('unique_code',$script->unique_code)->exists()){
            return false;
        };

        // Save and return the script.
        $script->save();
        return $script;
    }

    public static function createFile($script,...$parameters){
        
        // Save script in order to generate unique _id.
        $script->save();

        //Create File Writer
        $file = fopen(storage_path('app' . DIRECTORY_SEPARATOR . 'scripts' ) . DIRECTORY_SEPARATOR . $script->_id, 'w');

        // Simply slice the parameters of the function, ex: unique_code, company etc.
        $user_inputs = array_slice($parameters[0],0,-1);

        // Write each input as a row in to the file.
        foreach ($user_inputs as $parameter){
            fwrite($file,'#' . $parameter . PHP_EOL);
        }

        // Write the actual script itself.
        fwrite($file,$parameters[0][count($parameters[0]) -1]);

        // Close the file writer.
        fclose($file);

        // Fill values for the database.
        return Script::fillValues($script,$user_inputs);
    }

    public static function fillValues($script, ... $parameters){

        // Just a dummy control the correct parameters.
        $parameters = $parameters[0];

        // Loop through each parameters and set required datas just with specific names in order to save data.
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
