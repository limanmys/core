<?php

namespace App\Http\Controllers;

use App\Extension;
use App\Script;
use App\Server;

class ExtensionController extends Controller
{
    public static $protected = true;

    // Extension Management Home Page
    public function settings()
    {
        return respond(view('extensions.index'));
        // Return Extension View
    }


    // Extension Management Page
    public function one()
    {
        // Retrieve Extension from middleware. TODO retrieve extension from middleware.
        $extension = Extension::where('_id',\request('extension_id'))->first();

        // Go through all files and list them as tree style in array.
        $files = $this->tree(resource_path('views' . DIRECTORY_SEPARATOR . 'extensions' . DIRECTORY_SEPARATOR . strtolower($extension->name)));

        // Retrieve scripts from database.
        $scripts = Script::where('extensions', 'like', $extension->name)->get();

        // Return view with required parameters.
        return view('extensions.one', [
            "extension" => $extension,
            "files" => $files,
            "scripts" => $scripts
        ]);
    }

    // Search through folders and extract pages.
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


    public function index()
    {

        // Check if extension exists, if not redirect. TODO this should be done from middleware, not controller.
        if (Extension::where('_id', request('extension_id'))->exists() == false) {
            return redirect(route('home'));
        }

        // Get all Servers which have this extension.
        $servers = Server::where('extensions', 'like', \request('extension_id'))->get();
        $servers = Server::filterPermissions($servers);

        // Go through servers and create a city list, it will be used in javascript to highlight cities in map.
        $cities = [];
        foreach ($servers as $server) {
            if(!in_array($server->city,$cities)){
                array_push($cities,$server->city);
            }
        }
        // If user have only servers in one city, redirect to it.
        if(count($cities) == 1){
            return redirect(route('extension_city',[
                "extension_id" => request('extension_id'),
                "city" => $cities[0]
            ]));
        }
        return view('feature.index', [
            "cities" => implode(',',$cities)
        ]);
    }

    public function city()
    {
        // Get servers in requested city with requested extension.
        $servers = Server::where('city', \request('city'))->where('extensions', 'like', \request('extension_id'))->get();

        // Filters servers for permissions.
        $servers = Server::filterPermissions($servers);

        return view('feature.city', [
            "servers" => $servers
        ]);
    }

    public function server()
    {
        $extension = Extension::where('_id', \request('extension_id'))->first();
        $scripts = Script::where('extensions', 'like', $extension->name)->get();
        $server = \request('server');
        $outputs = [];
        foreach ($extension->views["index"] as $unique_code) {
            $script = $scripts->where('unique_code', $unique_code)->first();
            $output = $server->runScript($script, '');
            $output = str_replace('\n', '', $output);
            $outputs[$unique_code] = json_decode($output, true);
        }
        return view('feature.server', [
            "extension" => $extension,
            "scripts" => $scripts,
            "data" => $outputs,
            "view" => "index",
            "server" => $server
        ]);
    }

    public function route()
    {
        $extension = request('extension');
        $scripts = Script::where('extensions', 'like', '%' . $extension->name . '%')->get();
        $outputs = [];
        foreach (\request('scripts') as $script) {
            $parameters = '';
            foreach (explode(',', $script->inputs) as $input) {
                $parameters = $parameters . " " . \request(explode(':', $input)[0]);
            }
            $output = \request('server')->runScript($script, $parameters);
            $output = str_replace('\n', '', $output);
            $outputs[$script->unique_code] = json_decode($output, true);
        }

        $view = (\request()->ajax()) ? 'extensions.' . strtolower(\request('extension_id')) . '.' . \request('url') : 'feature.server';
        if (\request()->ajax() == false) {
            return view($view, [
                "result" => 200,
                "data" => $outputs,
                "view" => \request('url'),
                "extension" => $extension,
                "scripts" => $scripts,
            ]);
        } else {
            return 200;
        }
    }
}
