<?php

namespace App\Http\Controllers\Extension;

use App\Extension;
use App\Http\Controllers\Controller;
use App\Script;
use Exception;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Class MainController
 * @package App\Http\Controllers\Extension
 */
class MainController extends Controller
{
    /**
     * @return Factory|View
     */
    public function allServers()
    {
        // Get Servers of Extension
        $servers = extension()->servers();

        // Extract Cities of the Servers.
        $cities = array_values(objectToArray($servers, "city", "city"));
        system_log(7,"EXTENSION_SERVERS_INDEX",[
            "extension_id" => extension()->id
        ]);
        // Render View with Cities
        return view('extension_pages.index', [
            "cities" => implode(',', $cities)
        ]);
    }

    /**
     * @return BinaryFileResponse
     */
    public function download()
    {
        // Generate Extension Folder Path
        $path = env("EXTENSIONS_PATH") . strtolower(extension()->name);

        // Initialize Zip Archive Object to use it later.
        $zip = new ZipArchive;

        // Random Temporary Folder
        $exportedFile = '/tmp/' . Str::random();

        // Create Zip
        $zip->open($exportedFile . '.lmne', ZipArchive::CREATE | ZipArchive::OVERWRITE);

        // Iterator to search all files in folder.
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path),
            RecursiveIteratorIterator::LEAVES_ONLY
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

                // Ignore If it's db.json file
                if($relativePath == "db.json"){
                    continue;
                }

                // Add current file to archive
                $zip->addFile($filePath, 'views/' . $relativePath);
            }
        }

        // Create Folder to put scripts in
        $zip->addEmptyDir('scripts');

        // Simply, go through scripts and add them in to zip.
        foreach (extension()->scripts() as $script) {
            $zip->addFile(env('SCRIPTS_PATH') . $script->id, 'scripts/' . $script->unique_code . '.lmns');
        }

        // Add file
        $zip->addFile($path . DIRECTORY_SEPARATOR . "db.json", 'db.json');

        // Close/Compress zip
        $zip->close();

        system_log(6,"EXTENSION_DOWNLOAD",[
            "extension_id" => extension()->id
        ]);

        // Return zip as download and delete it after sent.
        return response()->download($exportedFile . '.lmne', extension()->name . "-" . extension()->version . ".lmne")->deleteFileAfterSend();
    }

    /**
     * @return JsonResponse|Response
     * @throws Exception
     */
    public function upload()
    {
        // Initialize Zip Archive Object to use it later.
        $zip = new ZipArchive;

        // Try to open zip file.
        if (!$zip->open(request()->file('extension'))) {
            system_log(7,"EXTENSION_UPLOAD_FAILED_CORRUPTED");
            return respond("Eklenti Dosyası Açılamıyor.", 201);
        }

        // Determine a random tmp folder to extract files
        $path = '/tmp/' . Str::random();

        // Extract Zip to the Temp Folder.
        $zip->extractTo($path);

        if (count(scandir($path)) == 3) {
            $path = $path . '/' . scandir($path)[2];
        }

        // Now that we have everything, let's extract database.
        $file = file_get_contents($path . '/db.json');

        $json = json_decode($file, true);

        // Check If Extension Already Exists.
        $extension = Extension::where('name', $json["name"])->first();

        if ($extension) {
            if ($extension->version == $json["version"]) {
                system_log(7,"EXTENSION_UPLOAD_FAILED_ALREADY_INSTALLED");
                return respond("Eklentinin bu sürümü zaten yüklü", 201);
            }
        }

        // Create extension object and fill values.
        if ($extension) {
            $new = $extension;
        } else {
            $new = new Extension();
        }
        $new->fill($json);
        $new->save();

        // Add User if not exists
        if ((intval(shell_exec("grep -c '^" . clean_score($new->id) . "' /etc/passwd"))) ? false : true) {
            shell_exec('sudo useradd -r -s /bin/sh ' . clean_score($new->id));
        }

        $extension_folder = env('EXTENSIONS_PATH') . strtolower($json["name"]);

        // Create folder.
        if (!is_dir($extension_folder)) {
            mkdir($extension_folder);
        }

        shell_exec("sudo cp " . $path . '/db.json' . " " . $extension_folder . DIRECTORY_SEPARATOR . "db.json");

        shell_exec('sudo chown ' . clean_score($new->id) . ':liman ' . $extension_folder);
        shell_exec('sudo chmod 770 ' . $extension_folder);

        shell_exec("sudo cp -r " . $path .  DIRECTORY_SEPARATOR . "views/* " . $extension_folder);

        shell_exec("sudo chown -R " . clean_score($new->id) . ':liman "' . $extension_folder. '"');
        shell_exec("sudo chmod -R 770 \"" . $extension_folder ."\"");

        shell_exec("sudo chown liman:". clean_score($new->id) . " " . $extension_folder . DIRECTORY_SEPARATOR . "db.json");
        shell_exec("sudo chmod 640 " . $extension_folder . DIRECTORY_SEPARATOR . "db.json");

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path . '/scripts/'),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            // Skip directories (they would be added automatically)
            if (!$file->isDir()) {
                if (substr($file->getFilename(), 0, 1) == "." || !Str::endsWith($file->getFilename(), ".lmns")) {
                    continue;
                }
                // Get real and relative path for current file
                $filePath = $file->getRealPath();

                $script = Script::readFromFile($filePath);
                if ($script) {
                    copy($filePath, env('SCRIPTS_PATH') . $script->id);
                }
            }
        }
        system_log(3,"EXTENSION_UPLOAD_SUCCESS",[
            "extension_id" => $new->id
        ]);

        return respond(route('extension_one', $new->id), 300);
    }


    public function newExtension()
    {
        $folder = env('EXTENSIONS_PATH') . strtolower(request('name'));

        if(Extension::where("name",request("name"))->exists()){
            return respond("Bu isimle zaten bir eklenti var.",201);
        }

        $ext = new Extension([
            "name" => request("name"),
            "version" => "0.0.1",
            "icon" => ""
        ]);
        $ext->save();

        $json = [
            "name" => request('name'),
            "publisher" => auth()->user()->name,
            "version" => "0.0.1",
            "database" => [],
            "widgets" => [],
            "views" => [
                [
                    "name" => "index",
                    "scripts" => ""
                ]
            ],
            "status" => 0,
            "service" => "",
            "support" => auth()->user()->email,
            "icon" => ""
        ];

        if (!is_dir($folder)) {
            mkdir($folder);
        }

        touch($folder . DIRECTORY_SEPARATOR . "db.json");

        file_put_contents($folder . DIRECTORY_SEPARATOR . "db.json",json_encode($json));


        if ((intval(shell_exec("grep -c '^" . clean_score($ext->id) . "' /etc/passwd"))) ? false : true) {
            shell_exec('sudo useradd -r -s /bin/sh ' . clean_score($ext->id));
        }

        shell_exec('sudo chown ' . clean_score($ext->id) . ':liman ' . $folder);
        shell_exec('sudo chmod 770 ' . $folder);

        touch($folder . '/index.blade.php');
        touch($folder . '/functions.php');

        shell_exec('sudo chown ' . clean_score($ext->id) . ':liman "' . trim($folder) . '/index.blade.php"');
        shell_exec('sudo chmod 664 "' . trim($folder) . '/index.blade.php"');

        shell_exec('sudo chown ' . clean_score($ext->id) . ':liman "' . trim($folder) . '/functions.php"');
        shell_exec('sudo chmod 664 "' . trim($folder) . '/functions.php"');

        shell_exec("sudo chown liman:". clean_score($ext->id) . " " . $folder . DIRECTORY_SEPARATOR . "db.json");
        shell_exec("sudo chmod 640 " . $folder . DIRECTORY_SEPARATOR . "db.json");

        system_log(6,"EXTENSION_CREATE",[
            "extension_id" => $ext->id
        ]);

        return respond(route('extension_one', $ext->id), 300);
    }
}
