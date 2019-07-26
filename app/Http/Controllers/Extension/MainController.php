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

use App\Classes\Packager\Control\StandardFile;
use App\Classes\Packager\Packager;

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

        $tempPath = "/tmp/" . Str::random() . ".zip";

        shell_exec("cd $path && zip -r $tempPath .");

        system_log(6,"EXTENSION_DOWNLOAD",[
            "extension_id" => extension()->id
        ]);

        // Return zip as download and delete it after sent.
        return response()->download($tempPath, extension()->name . "-" . extension()->version . ".lmne")->deleteFileAfterSend();
    }

    public function download_deb()
    {
        $path = env("EXTENSIONS_PATH") . strtolower(extension()->name);
        $ext_info = json_decode(file_get_contents($path . '/db.json'));
        $tempPath = "/tmp/" . Str::random();
        $postInstallPath = "/tmp/" . Str::slug($ext_info->name) . "_postinst.sh";
        shell_exec("sudo touch ".$postInstallPath);
        shell_exec("sudo chmod 777 ".$postInstallPath);
        shell_exec('sudo echo "sudo php /liman/server/artisan activate_extension '.strtolower(extension()->name).'" > '.$postInstallPath);
        $packageName = $ext_info->name . "-" . $ext_info->version . ".deb";
        $control = new StandardFile();
        $control
            ->setPackageName("liman-".Str::slug($ext_info->name))
            ->setVersion($ext_info->version)
            ->setMaintainer($ext_info->publisher, $ext_info->support)
            ->setProvides("liman-".Str::slug($ext_info->name))
            ->setDescription($ext_info->name);
        $packager = new Packager();
        $packager->setOutputPath($tempPath);
        $packager->setControl($control);
        $packager->mount(env("EXTENSIONS_PATH") . strtolower($ext_info->name), env("EXTENSIONS_PATH") . strtolower($ext_info->name));
        $packager->setPostInstallScript($postInstallPath);
        $packager->run();
        shell_exec($packager->build($packageName));
        shell_exec("sudo rm ".$postInstallPath);
        system_log(6,"EXTENSION_DEB_DOWNLOAD",[
            "extension_id" => extension()->id
        ]);
        return response()->download($packageName, $packageName)->deleteFileAfterSend();
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

        shell_exec("sudo mkdir -p $extension_folder");

        shell_exec("sudo cp -r " . $path . "/* " . $extension_folder . DIRECTORY_SEPARATOR);

        shell_exec('sudo chown ' . clean_score($new->id) . ':liman ' . $extension_folder);
        shell_exec('sudo chmod 770 ' . $extension_folder);

        shell_exec("sudo chown -R " . clean_score($new->id) . ':liman "' . $extension_folder. '"');
        shell_exec("sudo chmod -R 770 \"" . $extension_folder ."\"");

        shell_exec("sudo chown liman:". clean_score($new->id) . " " . $extension_folder . DIRECTORY_SEPARATOR . "db.json");
        shell_exec("sudo chmod 640 " . $extension_folder . DIRECTORY_SEPARATOR . "db.json");

        if(is_dir($path . '/scripts/')){
          $files = new RecursiveIteratorIterator(
              new RecursiveDirectoryIterator($path . '/scripts/'),
              RecursiveIteratorIterator::LEAVES_ONLY
          );
        }else {
          $files = [];
        }

        foreach ($files as $file) {
            // Skip directories (they would be added automatically)
            if (!$file->isDir()) {
                if (substr($file->getFilename(), 0, 1) == "." || !Str::endsWith($file->getFilename(), ".lmns")) {
                    continue;
                }
                // Get real and relative path for current file
                $filePath = $file->getRealPath();

                Script::readFromFile($filePath);
            }
        }
        system_log(3,"EXTENSION_UPLOAD_SUCCESS",[
            "extension_id" => $new->id
        ]);

        return respond("Eklenti Başarıyla yüklendi.",200);
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
            "icon" => "",
            "service" => "",
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

        shell_exec("mkdir " . $folder);
        shell_exec("mkdir " . $folder . DIRECTORY_SEPARATOR . "views");
        shell_exec("mkdir " . $folder . DIRECTORY_SEPARATOR . "scripts");

        touch($folder . DIRECTORY_SEPARATOR . "db.json");

        file_put_contents($folder . DIRECTORY_SEPARATOR . "db.json",json_encode($json));

        if ((intval(shell_exec("grep -c '^" . clean_score($ext->id) . "' /etc/passwd"))) ? false : true) {
            shell_exec('sudo useradd -r -s /bin/sh ' . clean_score($ext->id));
        }

        touch($folder . '/views/index.blade.php');
        touch($folder . '/views/functions.php');

        shell_exec('sudo chown -R ' . clean_score($ext->id) . ':liman ' . $folder);
        shell_exec('sudo chmod 770 ' . $folder);

        shell_exec("sudo chown liman:". clean_score($ext->id) . " " . $folder . DIRECTORY_SEPARATOR . "db.json");
        shell_exec("sudo chmod 640 " . $folder . DIRECTORY_SEPARATOR . "db.json");

        system_log(6,"EXTENSION_CREATE",[
            "extension_id" => $ext->id
        ]);

        return respond(route('extension_one', $ext->id), 300);
    }

    public function updateExtOrders()
    {
      foreach (json_decode(request('data')) as $extension) {
        $data = Extension::find($extension->id);
        $data->order = $extension->order;
        $data->save();
      }
      return respond('Sıralamalar güncellendi',200);
    }
}
