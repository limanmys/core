<?php

namespace App\Http\Controllers\Extension;

use App\Extension;
use App\Http\Controllers\Controller;
use App\Script;
use Illuminate\Support\Str;

/**
 * Class MainController
 * @package App\Http\Controllers\Extension
 */
class MainController extends Controller
{
    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function allServers()
    {
        // Get Servers of Extension
        $servers = extension()->servers();

        // Extract Cities of the Servers.
        $cities = array_values(objectToArray($servers, "city", "city"));

        // Render View with Cities
        return view('extension_pages.index', [
            "cities" => implode(',', $cities)
        ]);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function download()
    {
        // Generate Extension Folder Path
        $path = resource_path('views' . DIRECTORY_SEPARATOR . 'extensions' . DIRECTORY_SEPARATOR . strtolower(extension()->name));

        // Initialize Zip Archive Object to use it later.
        $zip = new \ZipArchive;

        // Random Temporary Folder
        $exportedFile = '/tmp/' . Str::random();

        // Create Zip
        $zip->open($exportedFile . '.lmne', \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        // Iterator to search all files in folder.
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        // Create Folder to put views in
        $zip->addEmptyDir('views');

        // Simply, go through files recursively and add them in to zip.
        foreach ($files as $file) {

            // Skip directories (they would be added automatically)
            if (!$file->isDir()) {

                // Get real and relative path for current file
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($path) + 1);

                // Add current file to archive
                $zip->addFile($filePath, 'views/' . $relativePath);
            }
        }

        // Create Folder to put scripts in
        $zip->addEmptyDir('scripts');

        // Simply, go through scripts and add them in to zip.
        foreach (extension()->scripts() as $script) {
            $zip->addFile(storage_path('app/scripts/') . $script->_id, 'scripts/' . $script->unique_code . '.lmns');
        }

        // Extract database in to json.
        file_put_contents($exportedFile . '_db.json', extension()->toJson());

        // Add file
        $zip->addFile($exportedFile . '_db.json', 'db.json');

        // Close/Compress zip
        $zip->close();

        // Return zip as download and delete it after sent.
        return response()->download($exportedFile . '.lmne', extension()->name . "-" . extension()->version . ".lmne")->deleteFileAfterSend();
    }


    /**
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     * @throws \Exception
     */
    public function upload()
    {
        // Initialize Zip Archive Object to use it later.
        $zip = new \ZipArchive;

        // Try to open zip file.
        if (!$zip->open(request()->file('extension'))) {
            return respond("Eklenti Dosyası Açılamıyor.", 201);
        }

        // Determine a random tmp folder to extract files
        $path = '/tmp/' . Str::random();

        // Extract Zip to the Temp Folder.
        $zip->extractTo($path);

        if(count(scandir($path)) == 3){
            $path = $path . '/' . scandir($path)[2];
        }

        // Now that we have everything, let's extract database.
        $file = file_get_contents($path . '/db.json');
        $json = json_decode($file, true);

        // Check If Extension Already Exists.
        $extension = Extension::where('name',$json["name"])->first();
        if($extension){
            if($extension->version == $json["version"]){
                return respond("Eklentinin bu sürümü zaten yüklü",201);
            }
        }

        // Create extension object and fill values.
        if($extension){
            $new = $extension;
        }else{
            $new = new Extension();
        }
        $new->fill($json);
        $new->save();

        if((intval(shell_exec("grep -c '^liman-: " . $new->_id  . "' /etc/passwd"))) ? false : true){
            shell_exec('sudo useradd -r -s /bin/sh liman-' . $new->_id);
        }

        $extension_folder = resource_path('views/extensions/' . strtolower($json["name"]));

        // Delete existing folder.
        if (is_dir($extension_folder)) {
            $this->rmdir_recursive($extension_folder);
        }

        mkdir($extension_folder);
        shell_exec('sudo chown ' . $new->_id . ':liman ' . $extension_folder);
        shell_exec('sudo chmod 770 ' . $extension_folder);

        // Copy Views into the liman.
        $views = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path . '/views/'),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($views as $view){
            // Skip directories (they would be added automatically)
            if (!$view->isDir()) {
                if (substr($view->getFilename(), 0, 1) == ".") {
                    continue;
                }

                copy($view->getRealPath(),$extension_folder . DIRECTORY_SEPARATOR . $view->getFilename());
                shell_exec('sudo chown liman-' . $new->_id . ':liman "' . $extension_folder . DIRECTORY_SEPARATOR . $view->getFilename() . '"');
                shell_exec('sudo chmod 664 "' . $extension_folder . DIRECTORY_SEPARATOR . $view->getFilename() . '"');
            }

        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path . '/scripts/'),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            // Skip directories (they would be added automatically)
            if (!$file->isDir()) {
                if (substr($file->getFilename(), 0, 1) == ".") {
                    continue;
                }

                // Get real and relative path for current file
                $filePath = $file->getRealPath();

                $script = Script::readFromFile($filePath);
                if($script){
                    copy($filePath,storage_path('app' . DIRECTORY_SEPARATOR . 'scripts') . DIRECTORY_SEPARATOR . $script->_id);
                }
            }
        }

        $folder = resource_path('views/extensions/') . strtolower(request('name'));

        return respond(route('extension_one',$new->_id),300);
    }

    /**
     * @param $dir
     */
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

    public function newExtension()
    {
        $ext = new Extension([
            "name" => request('name'),
            "publisher" => auth()->user()->name,
            "version" => "0.1",
            "database" => [],
            "widgets" => [],
            "views" => [
                [
                    "name" => "index",
                    "scripts" => ""
                ],
                [
                    "name" => "functions",
                    "scripts" => ""
                ]
            ],
            "status" => 0,
            "service" => ""
        ]);

        $ext->save();

        $folder = resource_path('views/extensions/') . strtolower(request('name'));

        mkdir($folder);
        shell_exec('sudo chown ' . $ext->_id . ':liman ' . $folder);
        shell_exec('sudo chmod 770 ' . $folder);

        touch($folder  . '/index.blade.php');
        touch($folder  . '/functions.php');

        if((intval(shell_exec("grep -c '^liman-: " . $ext->_id  . "' /etc/passwd"))) ? false : true){
            shell_exec('sudo useradd -r -s /bin/sh liman-' . $ext->_id);
        }

        shell_exec('sudo chown liman-' . $ext->_id . ':liman "' . trim($folder) . '/index.blade.php"');
        shell_exec('sudo chmod 664 "' . trim($folder) . '/index.blade.php"');

        shell_exec('sudo chown liman-' . $ext->_id . ':liman "' . trim($folder) . '/functions.php"');
        shell_exec('sudo chmod 664 "' . trim($folder) . '/functions.php"');
        
        return respond(route('extension_one',$ext->_id),300);
    }
}
