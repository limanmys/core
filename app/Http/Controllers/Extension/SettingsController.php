<?php

namespace App\Http\Controllers\Extension;

use App\Extension;
use App\Http\Controllers\Controller;
use App\Script;

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
        return view('extension_pages.manager');
    }

    // Extension Management Page

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function settings_one()
    {
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
    public function getScriptsOfView(){
        $extension = Extension::find(request('extension_id'));
        if(array_key_exists(request('view'),$extension->views)){
            $arr = $extension->views[request('view')];
        }else{
            $arr = [];
        }
        return $arr;
    }

    /**
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function addScriptToView(){
        $extension = Extension::find(request('extension_id'));
        $temp = $extension->views;
        if(array_key_exists(request('view'),$extension->views)){
            array_push($temp[request('view')],request('unique_code'));
        }else{
            $temp[request('view')] = [request('unique_code')];
        }
        $extension->views = $temp;
        $extension->save();
        return response(__("Başarıyla Eklendi."),200);
    }

    /**
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function removeScriptFromView(){
        $extension = Extension::find(request('extension_id'));
        $temp = $extension->views;
        if(array_key_exists(request('view'),$extension->views)){
            unset($temp[request('view')][array_search(request('unique_code'), $temp[request('view')])]);
        }else{
            return response(__("Sayfa Bulunamadı."),404);
        }
        return response(__("Başarıyla kaldırıldı."),200);
    }
}
