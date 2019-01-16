<?php

namespace App\Http\Controllers\Extension;

use App\Extension;
use App\Script;
use App\Http\Controllers\Controller;

class OneController extends Controller
{
    public function server()
    {
        // Get Extension Data.
        $extension = Extension::where('_id', \request('extension_id'))->first();

        // First, check requested server has key.
        $server = \request('server');


        if($server->key == null){

            // Redirect user if requested server is not serverless.
            if($extension->serverless != "true"){

                // Redirect user to keys.
                return respond(route('keys'),300);
            }

            $scripts = [];
            $outputs = [];

        }else{
            // Get extension scripts
            $scripts = Script::extension($extension->name);

            // Get server object from middleware.

            $outputs = [];

            // Go through each required scripts and run each of them.
            foreach ($extension->views["index"] as $unique_code) {

                // Get Script
                $script = $scripts->where('unique_code', $unique_code)->first();
                // Run Script with no parameters.
                $output = $server->runScript($script, '');

                // Decode output and set it into outputs array.
                $output = str_replace('\n', '', $output);
                $outputs[$unique_code] = json_decode($output, true);
            }

        }
        // Return all required parameters.
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
        // Get Extension from Middleware
        $extension = request('extension');

        // Get Scripts of extension.
        $scripts = Script::extension($extension->name);
        $outputs = [];

        // Go through each required scripts of page and run them with proper parameters.
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
        if (!\request()->ajax()) {
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

    public function runFunction(){
        $extension = Extension::where('_id',request('extension_id'))->first();
        require(base_path('resources/views/extensions/' . strtolower($extension->name) . '/functions.php'));
        call_user_func('hello_world');

    }

}
