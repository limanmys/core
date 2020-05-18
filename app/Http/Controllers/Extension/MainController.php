<?php

namespace App\Http\Controllers\Extension;

use App\Extension;
use App\Http\Controllers\Controller;
use Exception;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Illuminate\Support\Facades\Validator;

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
        system_log(7, "EXTENSION_SERVERS_INDEX", [
            "extension_id" => extension()->id,
        ]);
        if (count($cities) == 1) {
            return redirect(
                route("extension_city", [
                    "extension_id" => extension()->id,
                    "city" => $cities[0],
                ])
            );
        }
        // Render View with Cities
        return view('extension_pages.index', [
            "cities" => implode(',', $cities),
        ]);
    }

    /**
     * @return BinaryFileResponse
     */
    public function download()
    {
        // Generate Extension Folder Path
        $path = "/liman/extensions/" . strtolower(extension()->name);
        $tempPath = "/tmp/" . Str::random() . ".zip";

        // Zip the current extension
        shell_exec("cd $path && zip -r $tempPath .");

        system_log(6, "EXTENSION_DOWNLOAD", [
            "extension_id" => extension()->id,
        ]);

        // Return zip as download and delete it after sent.
        return response()
            ->download(
                $tempPath,
                extension()->name . "-" . extension()->version . ".lmne"
            )
            ->deleteFileAfterSend();
    }

    /**
     * @return JsonResponse|Response
     * @throws Exception
     */
    public function upload()
    {
        hook('extension_upload_attempt', [
            "request" => request()->all(),
        ]);

        $flag = Validator::make(request()->all(), [
            'extension' => 'required | max:5000000',
        ]);
        try {
            $flag->validate();
        } catch (\Exception $exception) {
            return respond("Lütfen geçerli bir eklenti giriniz.", 201);
        }

        $zipFile = request()->file('extension');
        if (
            endsWith(
                request()
                    ->file('extension')
                    ->getClientOriginalName(),
                ".signed"
            )
        ) {
            $verify = trim(
                shell_exec(
                    "gpg --verify --status-fd 1 " .
                        request()
                            ->file('extension')
                            ->path() .
                        " | grep GOODSIG || echo 0"
                )
            );
            if (!(bool) $verify) {
                return respond("Eklenti dosyanız doğrulanamadı.", 201);
            }
            $decrypt = trim(
                shell_exec(
                    "gpg --status-fd 1 -d -o '/tmp/" .
                        request()
                            ->file('extension')
                            ->getClientOriginalName() .
                        "' " .
                        request()
                            ->file('extension')
                            ->path() .
                        " | grep FAILURE > /dev/null && echo 0 || echo 1"
                )
            );
            if (!(bool) $decrypt) {
                return respond(
                    "Eklenti dosyası doğrulanırken bir hata oluştu!.",
                    201
                );
            }
            $zipFile =
                "/tmp/" .
                request()
                    ->file('extension')
                    ->getClientOriginalName();
        } else {
            if (!request()->has('force')) {
                return respond(
                    "Bu eklenti imzalanmamış bir eklenti, yine de kurmak istediğinize emin misiniz?",
                    203
                );
            }
        }
        // Initialize Zip Archive Object to use it later.
        $zip = new ZipArchive();

        // Try to open zip file.
        if (!$zip->open($zipFile)) {
            system_log(7, "EXTENSION_UPLOAD_FAILED_CORRUPTED");
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

        if(array_key_exists("supportedLiman",$json) && version_compare($json["supportedLiman"],getVersion()) < 0){
            return respond("Bu eklentiyi yükleyebilmek için Liman'ı güncellemelisiniz, gerekli minimum sürüm " . $json["supportedLiman"],201);
        }

        if (isset($verify)) {
            $json["issuer"] = explode(" ", $verify, 4)[3];
        } else {
            $json["issuer"] = "";
        }

        // Check If Extension Already Exists.
        $extension = Extension::where('name', $json["name"])->first();

        if ($extension) {
            if ($extension->version == $json["version"]) {
                system_log(7, "EXTENSION_UPLOAD_FAILED_ALREADY_INSTALLED");
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
        if (
            intval(
                shell_exec("grep -c '^" . cleanDash($new->id) . "' /etc/passwd")
            )
                ? false
                : true
        ) {
            shell_exec('sudo useradd -r -s /bin/sh ' . cleanDash($new->id));
        }

        $extension_folder = "/liman/extensions/" . strtolower($json["name"]);
        $passPath = '/liman/keys' . DIRECTORY_SEPARATOR . $new->id;
        file_put_contents($passPath, Str::random(32));

        shell_exec(
            "
            sudo chown liman:" .
                cleanDash($new->id) .
                " $passPath;
            sudo chmod 640 $passPath;
            sudo mkdir -p $extension_folder;
            sudo cp -r " .
                $path .
                "/* " .
                $extension_folder .
                DIRECTORY_SEPARATOR .
                ";
            sudo chown " .
                cleanDash($new->id) .
                ":liman $extension_folder;
            sudo chmod 770 $extension_folder;
            sudo chown -R " .
                cleanDash($new->id) .
                ":liman $extension_folder;
            sudo chmod -R 770 $extension_folder;
            sudo chown liman:" .
                cleanDash($new->id) .
                " " .
                $extension_folder .
                DIRECTORY_SEPARATOR .
                "db.json;
            sudo chmod 640 " .
                $extension_folder .
                DIRECTORY_SEPARATOR .
                "db.json;
        "
        );

        system_log(3, "EXTENSION_UPLOAD_SUCCESS", [
            "extension_id" => $new->id,
        ]);

        return respond("Eklenti Başarıyla yüklendi.", 200);
    }

    public function newExtension()
    {
        $name = trim(request('name'));
        $folder = "/liman/extensions/" . strtolower($name);

        preg_match('/[A-Za-z-]+/', request("name"), $output);
        if (empty($output) || $output[0] != $name) {
            return respond(
                "Eklenti isminde yalnızca harflere izin verilmektedir.",
                201
            );
        }

        if (Extension::where("name", request("name"))->exists()) {
            return respond("Bu isimle zaten bir eklenti var.", 201);
        }
        $ext = Extension::create([
            "name" => request("name"),
            "version" => "0.0.1",
            "icon" => "",
            "service" => "",
            "language" => request('language'),
        ]);

        $json = [
            "name" => $name,
            "publisher" => auth()->user()->name,
            "version" => "0.0.1",
            "database" => [],
            "widgets" => [],
            "views" => [
                [
                    "name" => "index",
                    "scripts" => "",
                ],
            ],
            "language" => request('language'),
            "status" => 0,
            "service" => "",
            "supportedLiman" => file_get_contents(storage_path('VERSION')),
            "support" => auth()->user()->email,
            "icon" => "",
        ];

        shell_exec(
            "
            mkdir $folder;
            mkdir $folder" .
                DIRECTORY_SEPARATOR .
                "views;
            mkdir $folder" .
                DIRECTORY_SEPARATOR .
                "scripts;
        "
        );

        touch($folder . DIRECTORY_SEPARATOR . "db.json");

        file_put_contents(
            $folder . DIRECTORY_SEPARATOR . "db.json",
            json_encode($json, JSON_PRETTY_PRINT)
        );

        if (
            intval(
                shell_exec("grep -c '^" . cleanDash($ext->id) . "' /etc/passwd")
            )
                ? false
                : true
        ) {
            shell_exec('sudo useradd -r -s /bin/sh ' . cleanDash($ext->id));
        }

        $passPath = '/liman/keys' . DIRECTORY_SEPARATOR . $ext->id;
        file_put_contents($passPath, Str::random(32));
        shell_exec(
            "
            sudo chown liman:" .
                cleanDash($ext->id) .
                " $passPath;
            sudo chmod 640 $passPath;
        "
        );

        foreach (sandbox(request('language'))->getInitialFiles() as $file) {
            touch($folder . "/views/$file");
        }

        shell_exec(
            "
            sudo chown -R " .
                cleanDash($ext->id) .
                ":liman $folder;
            sudo chmod -R 770 $folder;
            sudo chown liman:" .
                cleanDash($ext->id) .
                " $folder" .
                DIRECTORY_SEPARATOR .
                "db.json;
            sudo chmod 640  $folder" .
                DIRECTORY_SEPARATOR .
                "db.json;
        "
        );

        system_log(6, "EXTENSION_CREATE", [
            "extension_id" => $ext->id,
        ]);
        return respond(route('extension_one', $ext->id), 300);
    }

    public function updateExtOrders()
    {
        foreach (json_decode(request('data')) as $extension) {
            Extension::where('id', $extension->id)->update([
                "order" => $extension->order,
            ]);
        }
        return respond('Sıralamalar güncellendi', 200);
    }
}
