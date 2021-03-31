<?php

namespace App\Http\Controllers\Extension;

use App\Models\Extension;
use App\Http\Controllers\Controller;
use Exception;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\View\View;
use App\Jobs\ExtensionUpdaterJob;
use Illuminate\Contracts\Bus\Dispatcher;
use App\System\Command;

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
        return magicView('extension_pages.index', [
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
        Command::runLiman("cd @{:path} && zip -r @{:tempPath} .", [
            'path' => $path,
            'tempPath' => $tempPath
        ]);

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

        validate([
            'extension' => 'required|max:5000000',
        ]);

        $verify = false;
        $zipFile = request()->file('extension');
        if (
            endsWith(
                request()
                    ->file('extension')
                    ->getClientOriginalName(),
                ".signed"
            )
        ) {
            $verify = Command::runLiman(
                "gpg --verify --status-fd 1 @{:extension} | grep GOODSIG || echo 0",
                ['extension' => request()->file('extension')->path()]
            );
            if (!(bool) $verify) {
                return respond("Eklenti dosyanız doğrulanamadı.", 201);
            }
            $decrypt = Command::runLiman(
                "gpg --status-fd 1 -d -o '/tmp/{:originalName}' @{:extension} | grep FAILURE > /dev/null && echo 0 || echo 1",
                [
                    'originalName' => "ext-".basename(request()->file('extension')->path()),
                    'extension' => request()->file('extension')->path()
                ]
            );
            if (!(bool) $decrypt) {
                return respond(
                    "Eklenti dosyası doğrulanırken bir hata oluştu!.",
                    201
                );
            }
            $zipFile =
                "/tmp/ext-" .
                basename(
                    request()->file('extension')->path()
                );
        } else {
            if (!request()->has('force')) {
                return respond(
                    "Bu eklenti imzalanmamış bir eklenti, yine de kurmak istediğinize emin misiniz?",
                    203
                );
            }
        }
        list($error, $new) = $this->setupNewExtension($zipFile, $verify);

        if ($error) {
            return $error;
        }

        system_log(3, "EXTENSION_UPLOAD_SUCCESS", [
            "extension_id" => $new->id,
        ]);

        return respond("Eklenti Başarıyla yüklendi.", 200);
    }

    public function setupNewExtension($zipFile, $verify = false)
    {
        // Initialize Zip Archive Object to use it later.
        $zip = new ZipArchive();

        // Try to open zip file.
        if (!$zip->open($zipFile)) {
            system_log(7, "EXTENSION_UPLOAD_FAILED_CORRUPTED");
            return [respond("Eklenti Dosyası Açılamıyor.", 201), null];
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

        preg_match('/[A-Za-z-]+/', $json["name"], $output);
        if (empty($output) || $output[0] != $json["name"]) {
            return respond(
                "Eklenti isminde yalnızca harflere izin verilmektedir.",
                201
            );
        }

        if (
            array_key_exists("supportedLiman", $json) &&
            getVersionCode() < intval($json["supportedLiman"])
        ) {
            return [
                respond(
                    "Bu eklentiyi yükleyebilmek için Liman'ı güncellemelisiniz, gerekli minimum liman sürüm kodu" .
                        $json["supportedLiman"],
                    201
                ),
                null,
            ];
        }

        if ($verify) {
            $json["issuer"] = explode(" ", $verify, 4)[3];
        } else {
            $json["issuer"] = "";
        }

        // Check If Extension Already Exists.
        $extension = Extension::where('name', $json["name"])->first();

        if ($extension) {
            if ($extension->version == $json["version"]) {
                system_log(7, "EXTENSION_UPLOAD_FAILED_ALREADY_INSTALLED");
                return [respond("Eklentinin bu sürümü zaten yüklü", 201), null];
            }
        }

        // Create extension object and fill values.
        if ($extension) {
            $new = $extension;
        } else {
            $new = new Extension();
        }
        
        $new->fill($json);
        $new->status = "1";
        $new->save();
        
        if (array_key_exists("dependencies", $json) && $json["dependencies"] != "") {
            rootSystem()->installPackages($json["dependencies"]);
        }

        $system = rootSystem();

        $system->userAdd($new->id);

        $passPath = '/liman/keys' . DIRECTORY_SEPARATOR . $new->id;
        file_put_contents($passPath, Str::random(32));

        $extension_folder = "/liman/extensions/" . strtolower($json["name"]);

        Command::runLiman('mkdir -p @{:extension_folder}', [
            'extension_folder' => $extension_folder
        ]);

        Command::runLiman("cp -r {:path}/* {:extension_folder}/.", [
            'extension_folder' => $extension_folder,
            'path' => $path
        ]);
        $system->fixExtensionPermissions($new->id, $new->name);

        return [null, $new];
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

        if (!in_array(request('template'), array_keys((array) fetchExtensionTemplates()->templates))) {
            return respond("Lütfen geçerli bir tip seçiniz.", 201);
        }

        $template = request('template');
        $template_folder = storage_path('extension_templates/'.$template.'/');
        Command::runLiman("cp -r @{:template_folder} @{:folder}", [
            'template_folder' => $template_folder,
            'folder' => $folder
        ]);
        
        foreach (glob("$folder/*.json") as $file) {
            $content = file_get_contents($file);
            $content = str_replace([
                "<NAME>",
                "<PUBLISHER>",
                "<SUPPORTED_LIMAN>",
                "<SUPPORT>"
            ], [
                request("name"),
                auth()->user()->name,
                file_get_contents(storage_path('VERSION')),
                auth()->user()->email
            ], $content);
            file_put_contents($file, $content);
        }

        $json = json_decode(file_get_contents("$folder/db.json"));
        $ext = Extension::create([
            "name" => request("name"),
            "version" => "0.0.1",
            "icon" => "",
            "service" => "",
            "language" => $json->language,
        ]);

        $system = rootSystem();
        
        $system->userAdd($ext->id);

        $passPath = '/liman/keys' . DIRECTORY_SEPARATOR . $ext->id;
        file_put_contents($passPath, Str::random(32));

        request()->request->add(['server' => "none"]);
        request()->request->add(['extension_id' => $ext->id]);

        $system->fixExtensionPermissions($ext->id, $ext->name);

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

    public function autoUpdateExtension()
    {
        $json = json_decode(
            file_get_contents(storage_path('extension_updates')),
            true
        );
        $collection = collect($json);
        $obj = $collection
            ->where('extension_id', request("extension_id"))
            ->first();

        if (!$obj) {
            return respond("Eklenti Bulunamadı", 201);
        }

        $job = (new ExtensionUpdaterJob(
            request("extension_id"),
            $obj["versionCode"],
            $obj["downloadLink"],
            $obj["hashSHA512"],
            true
        ))->onQueue('system_updater');

        // Dispatch job right away.
        $job_id = app(Dispatcher::class)->dispatch($job);

        return respond(
            "Talebiniz başarıyla alındı, eklenti güncellendiğinde bildirim alacaksınız."
        );
    }
}
