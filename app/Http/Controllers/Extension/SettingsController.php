<?php

namespace App\Http\Controllers\Extension;

use App\Extension;
use App\Http\Controllers\Controller;
use App\Script;
use Illuminate\Support\Str;

/**
 * Class SettingsController
 * @package App\Http\Controllers\Extension
 */
class SettingsController extends Controller
{
    // Extension Management Home Page
    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function settings_all()
    {
        system_log(7,"EXTENSION_LIST");
        return view('extension_pages.manager');
    }

    public function allServersApi()
    {
        return response()->json(extensions());
    }

    // Extension Management Page

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function settings_one()
    {
        // Go through all files and list them as tree style in array.
        $files = $this->tree(env('EXTENSIONS_PATH') . strtolower(extension()->name));

        // Retrieve scripts from database.
        $scripts = Script::where('extensions', 'like', extension()->name)->get();

        $functionsFile = env('EXTENSIONS_PATH') . strtolower(extension()->name) . "/functions.php";
        if (is_file($functionsFile)) {
            $functionsFile = file_get_contents(env('EXTENSIONS_PATH') . strtolower(extension()->name) . "/functions.php");
            preg_match_all('/^\s*function (.*)(?=\()/m', $functionsFile, $results);
            $functions = [];
            foreach ($results[1] as $result) {
                array_push($functions, [
                    "name" => $result
                ]);
            }
        } else {
            $functions = [];
        }

        system_log(7,"EXTENSION_SETTINGS_PAGE",[
            "extension_id" => extension()->_id,
        ]);

        // Return view with required parameters.
        return view('extension_pages.one', [
            "files" => $files,
            "scripts" => $scripts,
            "functions" => $functions
        ]);
    }

    // Search through folders and extract pages.

    /**
     * @param $path
     * @return array
     */
    private function tree($path)
    {
        // If file is not path, simply return.
        if (!is_dir($path)) {
            return [];
        }

        // List files under path
        $files = scandir($path);

        // Ignore linux filesystem' '.' and '..' files.
        unset($files[0]);
        unset($files[1]);

        // Remake array because of corrupted index.
        $files = array_values($files);

        // Loop through each files
        foreach ($files as $file) {

            // Create full path of file.
            $newPath = $path . DIRECTORY_SEPARATOR . $file;

            // If new path is directory, go through same process recursively.
            if (is_dir($newPath)) {
                // Run same process.
                $files[$file] = $this->tree($path . DIRECTORY_SEPARATOR . $file);

                // Delete item from array since that's array not a file.
                $index = array_search($file, $files);
                unset($files[$index]);
            }
        }
        return $files;
    }

    /**
     * @return array
     */
    public function getScriptsOfView()
    {
        $extension = Extension::find(request('extension_id'));
        if (array_key_exists(request('view'), $extension->views)) {
            $arr = $extension->views[request('view')];
        } else {
            $arr = [];
        }
        system_log(7,"EXTENSION_VIEW_SCRIPTS",[
            "extension_id" => extension()->id,
        ]);

        return $arr;
    }

    public function update()
    {
        $extension = json_decode(file_get_contents(env("EXTENSIONS_PATH") .strtolower(extension()->name) . DIRECTORY_SEPARATOR . "db.json"),true);

        if (request('type') == "general") {
            $params = request()->all();
            extension()->update($params);
            $extension["icon"] = request("icon");
            $extension["service"] = request("service");
            $extension["version"] = request("version");
            $extension["verification"] = request("verification");
        } else {
            $values = $extension[request('table')];
            foreach ($values as $key => $value) {
                if ($value["name"] == request('name_old')) {
                    switch (request('table')) {
                        case "database":
                            $values[$key]["variable"] = request('variable');
                            $values[$key]["type"] = request('type');
                            $values[$key]["name"] = request('name');
                            break;
                        case "widgets":
                            $values[$key]["target"] = request('target');
                            $values[$key]["type"] = request('type');
                            $values[$key]["name"] = request('name');
                            $values[$key]["icon"] = request('icon');
                            break;
                        case "views":
                            rename(env(
                                'EXTENSIONS_PATH') . strtolower(extension()->name) . DIRECTORY_SEPARATOR . request('name_old') . '.blade.php',
                                env('EXTENSIONS_PATH') . strtolower(extension()->name) . DIRECTORY_SEPARATOR . request('name') . '.blade.php');
                            $values[$key]["scripts"] = request('scripts');
                            $values[$key]["name"] = request('name');
                            break;
                    }
                    break;
                }
            }
            $extension[request("table")] = $values;
        }

        file_put_contents(env("EXTENSIONS_PATH") .strtolower(extension()->name) . DIRECTORY_SEPARATOR . "db.json",json_encode($extension));

        system_log(7,"EXTENSION_SETTINGS_UPDATE",[
            "extension_id" => extension()->_id,
            "settings_type" => request('table')
        ]);

        return respond("Guncellendi", 200);
    }

    public function add()
    {
        $extension = json_decode(file_get_contents(env("EXTENSIONS_PATH") .strtolower(extension()->name) . DIRECTORY_SEPARATOR . "db.json"),true);

        $values = $extension[request('table')];
        switch (request('table')) {
            case "database":
                array_push($values, [
                    "variable" => request('variable'),
                    "type" => request('type'),
                    "name" => request('name'),
                ]);
                break;
            case "widgets":
                array_push($values, [
                    "target" => request('target'),
                    "type" => request('type'),
                    "name" => request('name'),
                    "icon" => request('icon'),
                ]);
                break;
            case "views":
                array_push($values, [
                    "scripts" => request('scripts'),
                    "name" => request('name'),
                ]);
                $file = env('EXTENSIONS_PATH') . strtolower(extension()->name) . '/' . request('name') . '.blade.php';

                if(!is_file($file)){
                    touch($file);
                }

                break;
        }
        $extension[request('table')] = $values;

        file_put_contents(env("EXTENSIONS_PATH") .strtolower(extension()->name) . DIRECTORY_SEPARATOR . "db.json",json_encode($extension));

        system_log(7,"EXTENSION_SETTINGS_ADD",[
            "extension_id" => extension()->id,
            "settings_type" => request('table')
        ]);

        return respond("Eklendi", 200);
    }

    public function remove()
    {
        $extension = json_decode(file_get_contents(env("EXTENSIONS_PATH") .strtolower(extension()->name) . DIRECTORY_SEPARATOR . "db.json"),true);

        $values = $extension[request('table')];
        foreach ($values as $key => $value) {
            if ($value["name"] == request('name')) {
                unset($values[$key]);
                break;
            }
        }
        if (request('table') == "views") {
            $file = env('EXTENSIONS_PATH') . strtolower(extension()->name) . '/' . request('name') . '.blade.php';
            if(is_file($file)){
                unlink($file);
            }
        }
        $extension[request('table')] = $values;

        file_put_contents(env("EXTENSIONS_PATH") .strtolower(extension()->name) . DIRECTORY_SEPARATOR . "db.json",json_encode($extension));

        system_log(7,"EXTENSION_SETTINGS_REMOVE",[
            "extension_id" => extension()->id,
            "settings_type" => request('table')
        ]);

        return respond("Eklenti Silindi.", 200);
    }

}
