<?php

namespace App\Http\Controllers\Extension;

use App\Extension;
use App\Script;
use App\Http\Controllers\Controller;

/**
 * Class OneController
 * @package App\Http\Controllers\Extension
 */
class OneController extends Controller
{
    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|\Illuminate\Routing\Redirector|\Illuminate\View\View
     * @throws \Throwable
     */
    public function server()
    {
        // Now that we have server, let's check if required parameters set for extension.
        foreach (extension()->database as $setting) {
            if (!array_key_exists(server()->_id,auth()->user()->settings) ||
                !array_key_exists(extension()->_id,auth()->user()->settings[server()->_id]) ||
                !array_key_exists($setting["variable"], auth()->user()->settings[server()->_id][extension()->_id])) {
                return redirect(route('extension_server_settings_page', [
                    "server_id" => server()->_id,
                    "extension_id" => extension()->_id
                ]));
            }
        }

        $outputs = [];

        // Go through each required scripts and run each of them.
        $views = extension()->views;
        foreach ($views as $view){
            if($view["name"] == "index"){
                $scripts = explode(',',$view["scripts"]);
                if(count($scripts) == 1 && $scripts[0] == ""){
                    break;
                }
                foreach ($scripts as $unique_code){
                    // Get Script
                    $script = extension()->scripts()->where('unique_code', $unique_code)->first();

                    // Check if required script is available or not.
                    if (!$script) {
                        return respond("Eklenti için gerekli olan betik yüklü değil, lütfen yöneticinizle görüşün.", 404);
                    }

                    // Run Script with no parameters.
                    $output = server()->runScript($script, '');

                    // Decode output and set it into outputs array.
                    $output = str_replace('\n', '', $output);
                    $outputs[$unique_code] = json_decode($output, true);
                }
                break;
            }
        }

        // Return all required parameters.
        return view('extension_pages.server', [
            "extension" => extension(),
            "scripts" => extension()->scripts(),
            "data" => $outputs,
            "view" => "index",
            "server" => server()
        ]);
    }


    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\JsonResponse|\Illuminate\Http\Response|\Illuminate\View\View
     * @throws \Throwable
     */
    public function route()
    {
        $outputs = [];

        // Go through each required scripts of page and run them with proper parameters.
        foreach (\request('scripts') as $script) {
            $parameters = '';
            if(!$script){
                abort(504,"Eklenti için gereken betik bulunamadı");
            }
            foreach (explode(',', $script->inputs) as $input) {
                $parameters = $parameters . " " . \request(explode(':', $input)[0]);
            }
            $output = server()->runScript($script, $parameters);
            $output = str_replace('\n', '', $output);
            $outputs[$script->unique_code] = json_decode($output, true);
        }

        $view = (\request()->ajax()) ? 'extensions.' . strtolower(\request('extension_id')) . '.' . \request('url') : 'extension_pages.server';
        if (view()->exists($view) && request()->method() != "POST") {
            return view($view, [
                "result" => 200,
                "view" => \request('url'),
            ]);
        } else {
            return respond("Başarıyla tamamlandı.");
        }
    }

    /**
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function runFunction()
    {
        $extension = Extension::where('_id', request()->route('extension_id'))->first();
        require(base_path('resources/views/extensions/' . strtolower($extension->name) . '/functions.php'));
        if (function_exists(request('function_name'))) {
            return call_user_func(request('function_name'));
        } else {
            return respond("İşlev bulunamadı, lütfen yöneticinizle iletişime geçiniz.", 404);
        }
    }

    /**
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function serverSettings()
    {
        $extension_config = [];
        foreach (extension()->database as $key) {
            $extension_config[$key["variable"]] = request($key["variable"]);
        }

        $settings = auth()->user()->settings;

        $settings[server()->_id][extension()->_id] = $extension_config;
        \App\User::where('_id', auth()->id())->update([
            "settings" => $settings
        ]);

        return redirect(route('extension_map', [
            "extension_id" => extension()->_id
        ]));
    }

    /**
     * @return \Illuminate\Http\Response
     */
    public function serverSettingsPage()
    {
        $extension = Extension::where('_id', request()->route('extension_id'))->first();
        return response()->view('extension_pages.setup', [
            'extension' => $extension
        ]);
    }

    /**
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function remove()
    {
        try {
            self::rmdir_recursive(resource_path('views' . DIRECTORY_SEPARATOR . 'extensions' . DIRECTORY_SEPARATOR . strtolower(extension()->name)));
            extension()->delete();
        } catch (\Exception $exception) {
            return respond('Eklenti silinemedi', 201);
        }
        return respond('Eklenti Başarıyla Silindi');
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function page()
    {
        if(request('page_name') == "functions"){
            $fileName = request('page_name') . '.php';
        }else{
            $fileName = request('page_name') . '.blade.php';
        }
        $file = file_get_contents(resource_path('views/extensions/') . strtolower(extension()->name) . '/' . $fileName);
        return view('l.editor',[
            "file" => $file
        ]);
    }

    /**
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function updateCode()
    {
        $file = resource_path('views/extensions/') . strtolower(extension()->name) . '/' . request('page') . '.blade.php';
        file_put_contents($file,json_decode(request('code')));
        return respond("Kaydedildi",200);
    }

    private function rmdir_recursive($dir) {
        foreach(scandir($dir) as $file) {
            if ('.' === $file || '..' === $file){
                continue;
            }
            if (is_dir("$dir/$file")){
                $this->rmdir_recursive("$dir/$file");
            }else{
                unlink("$dir/$file");
            }
        }
        rmdir($dir);
    }
}
