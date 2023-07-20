<?php

namespace App\Http\Controllers\API\Settings;

use App\Http\Controllers\Controller;
use App\Models\Extension;
use App\Models\GolangLicense;
use App\Models\License;
use App\Models\Permission;
use App\System\Command;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use ZipArchive;

class ExtensionController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin');
    }

    public function index()
    {
        $extensions = Extension::orderBy('updated_at', 'DESC')->get()->map(function ($item) {
            $item->updated = Carbon::parse($item->getRawOriginal('updated_at'))->getPreciseTimestamp(3);
            $item->licensed = $item->license()->count() > 0;

            return $item;
        });

        return response()->json($extensions);
    }

    /**
     * Upload an extension to Liman systsem
     *
     * @throws Exception
     * @throws GuzzleException
     */
    public function upload()
    {
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
                '.signed'
            )
        ) {
            $verify = Command::runLiman(
                'gpg --verify --status-fd 1 @{:extension} | grep GOODSIG || echo 0',
                ['extension' => request()->file('extension')->path()]
            );
            if (! (bool) $verify) {
                return response()->json('Eklenti dosyanız doğrulanamadı.', 422);
            }
            $decrypt = Command::runLiman(
                "gpg --status-fd 1 -d -o '/tmp/{:originalName}' @{:extension} | grep FAILURE > /dev/null && echo 0 || echo 1",
                [
                    'originalName' => 'ext-'.basename((string) request()->file('extension')->path()),
                    'extension' => request()->file('extension')->path(),
                ]
            );
            if (! (bool) $decrypt) {
                return response()->json(
                    'Eklenti dosyası doğrulanırken bir hata oluştu!.',
                    500
                );
            }
            $zipFile =
                '/tmp/ext-'.
                basename(
                    (string) request()->file('extension')->path()
                );
        } else {
            if (! request()->has('force')) {
                return response()->json(
                    'Bu eklenti imzalanmamış bir eklenti, yine de kurmak istediğinize emin misiniz?',
                    203
                );
            }
        }
        [$error, $new] = $this->setupNewExtension($zipFile, $verify);

        if ($error) {
            return $error;
        }

        system_log(3, 'EXTENSION_UPLOAD_SUCCESS', [
            'extension_id' => $new->id,
        ]);

        return response()->json('Eklenti başarıyla yüklendi.', 200);
    }

    /**
     * Delete extension from file system
     *
     * @return JsonResponse|Response
     *
     * @throws GuzzleException
     */
    public function delete()
    {
        $ext_name = extension()->name;
        try {
            Command::runLiman(
                "rm -rf '/liman/extensions/{:extension}'",
                [
                    'extension' => strtolower((string) extension()->name),
                ]
            );
        } catch (\Exception) {
        }

        try {
            rootSystem()->userRemove(extension()->id);
            extension()->delete();
        } catch (\Exception) {
        }

        if (is_file(storage_path('extension_updates'))) {
            $json = json_decode(file_get_contents(storage_path('extension_updates')), true);
            for ($i = 0; $i < count($json); $i++) {
                if ($json[$i]['name'] == $ext_name) {
                    unset($json[$i]);
                }
            }
            file_put_contents(storage_path('extension_updates'), json_encode($json));
        }

        try {
            Permission::where('value', $ext_name)
                ->where('type', 'function')
                ->where('key', 'name')
                ->delete();
        } catch (\Exception) {
        }

        system_log(3, 'EXTENSION_REMOVE');

        return response()->json('Eklenti başarıyla silindi.');
    }

    /**
     * Add license to extension
     *
     * @return JsonResponse|Response
     */
    public function license(Request $request)
    {
        if (extension()->license_type != 'golang_standard') {
            License::updateOrCreate(
                ['extension_id' => extension()->id],
                ['data' => request('license')]
            );

            return response()->json('Lisans eklendi.');
        }

        if (! $request->server_id) {
            return response()->json('Lisans eklenemiyor!', 500);
        }

        $output = callExtensionFunction(
            extension(),
            $request->server_id,
            [
                'endpoint' => 'license',
                'type' => 'post',
                'data' => json_encode([
                    'license' => request('license'),
                ]),
            ]
        );

        $licenseType = new GolangLicense($output);
        if ($licenseType->getValid()) {
            License::updateOrCreate(
                ['extension_id' => extension()->id],
                ['data' => request('license')]
            );

            Cache::forget('extension_'.extension()->id.'_'.$request->server_id.'_license');

            return response()->json('Lisans eklendi.');
        }

        return response()->json('Lisans eklenemiyor!', 500);
    }

    /**
     * Download extension from Liman
     *
     * @return BinaryFileResponse
     */
    public function download()
    {
        // Generate Extension Folder Path
        $path = '/liman/extensions/'.strtolower((string) extension()->name);
        $tempPath = '/tmp/'.Str::random().'.zip';

        // Zip the current extension
        Command::runLiman('cd @{:path} && zip -r @{:tempPath} .', [
            'path' => $path,
            'tempPath' => $tempPath,
        ]);

        system_log(6, 'EXTENSION_DOWNLOAD', [
            'extension_id' => extension()->id,
        ]);

        // Return zip as download and delete it after sent.
        return response()
            ->download(
                $tempPath,
                extension()->name.'-'.extension()->version.'.lmne'
            )
            ->deleteFileAfterSend();
    }

    /**
     * Setup new extension
     *
     * This function handles extension setup steps (setting perms etc.)
     *
     * @return array
     *
     * @throws GuzzleException
     */
    private function setupNewExtension($zipFile, $verify = false)
    {
        // Initialize Zip Archive Object to use it later.
        $zip = new ZipArchive();

        // Try to open zip file.
        if (! $zip->open($zipFile)) {
            system_log(7, 'EXTENSION_UPLOAD_FAILED_CORRUPTED');

            return [response()->json('Eklenti dosyası açılamıyor.', 500), null];
        }

        // Determine a random tmp folder to extract files
        $path = '/tmp/'.Str::random();
        // Extract Zip to the Temp Folder.
        try {
            $zip->extractTo($path);
        } catch (\Exception) {
            return [response()->json('Eklenti dosyası açılamıyor.', 500), null];
        }

        if (count(scandir($path)) == 3) {
            $path = $path.'/'.scandir($path)[2];
        }

        // Now that we have everything, let's extract database.
        $file = file_get_contents($path.'/db.json');

        $json = json_decode($file, true);

        preg_match('/[A-Za-z-]+/', (string) $json['name'], $output);
        if (empty($output) || $output[0] != $json['name']) {
            return [response()->json('Eklenti isminde yalnızca harflere izin verilmektedir.', 422), null];
        }

        if (
            array_key_exists('supportedLiman', $json) &&
            getVersionCode() < intval($json['supportedLiman'])
        ) {
            return [
                response()->json(
                    __("Bu eklentiyi yükleyebilmek için Liman'ı güncellemelisiniz, gerekli minimum liman sürüm kodu").' '.
                    intval($json['supportedLiman']),
                    201
                ),
                null,
            ];
        }

        if ($verify) {
            $json['issuer'] = explode(' ', (string) $verify, 4)[3];
        } else {
            $json['issuer'] = '';
        }

        // Check If Extension Already Exists.
        $extension = Extension::where('name', $json['name'])->first();

        if ($extension) {
            if ($extension->version == $json['version']) {
                system_log(7, 'EXTENSION_UPLOAD_FAILED_ALREADY_INSTALLED');

                return [response()->json('Eklentinin bu sürümü zaten yüklü.', 422), null];
            }
        }

        // Create extension object and fill values.
        if ($extension) {
            $new = $extension;
        } else {
            $new = new Extension();
        }
        unset($json['issuer']);
        unset($json['status']);
        unset($json['order']);
        $new->fill($json);
        $new->status = '1';
        $new->save();

        if (array_key_exists('dependencies', $json) && $json['dependencies'] != '') {
            rootSystem()->installPackages($json['dependencies']);
        }

        $system = rootSystem();

        $system->userAdd($new->id);

        $passPath = '/liman/keys'.DIRECTORY_SEPARATOR.$new->id;

        Command::runSystem('chmod 760 @{:path}', [
            'path' => $passPath,
        ]);

        file_put_contents($passPath, Str::random(32));

        $extension_folder = '/liman/extensions/'.strtolower((string) $json['name']);

        Command::runLiman('mkdir -p @{:extension_folder}', [
            'extension_folder' => $extension_folder,
        ]);

        Command::runLiman('cp -r {:path}/* {:extension_folder}/.', [
            'extension_folder' => $extension_folder,
            'path' => $path,
        ]);
        $system->fixExtensionPermissions($new->id, $new->name);

        return [null, $new];
    }
}
