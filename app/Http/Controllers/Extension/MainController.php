<?php

namespace App\Http\Controllers\Extension;

use App\Http\Controllers\Controller;
use App\Jobs\ExtensionUpdaterJob;
use App\Models\Extension;
use App\Models\Permission;
use App\System\Command;
use App\User;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

/**
 * Extension Main Controller
 *
 * @extends Controller
 */
class MainController extends Controller
{
    /**
     * Get extension list
     *
     * @return array
     */
    public function extensions()
    {
        $extensions = [];
        foreach (server()->extensions() as $extension) {
            $extensions[$extension->id] = $extension->display_name;
        }
        return $extensions;
    }

    /**
     * Download extension from Liman
     *
     * @return BinaryFileResponse
     */
    public function download()
    {
        // Generate Extension Folder Path
        $path = '/liman/extensions/' . strtolower((string) extension()->name);
        $tempPath = '/tmp/' . Str::random() . '.zip';

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
                extension()->name . '-' . extension()->version . '.lmne'
            )
            ->deleteFileAfterSend();
    }

    /**
     * Upload an extension to Liman systsem
     *
     * @throws Exception
     * @throws GuzzleException
     */
    public function upload()
    {
        hook('extension_upload_attempt', [
            'request' => request()->all(),
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
                '.signed'
            )
        ) {
            $verify = Command::runLiman(
                'gpg --verify --status-fd 1 @{:extension} | grep GOODSIG || echo 0',
                ['extension' => request()->file('extension')->path()]
            );
            if (! (bool) $verify) {
                return respond('Eklenti dosyanız doğrulanamadı.', 201);
            }
            $decrypt = Command::runLiman(
                "gpg --status-fd 1 -d -o '/tmp/{:originalName}' @{:extension} | grep FAILURE > /dev/null && echo 0 || echo 1",
                [
                    'originalName' => 'ext-' . basename((string) request()->file('extension')->path()),
                    'extension' => request()->file('extension')->path(),
                ]
            );
            if (! (bool) $decrypt) {
                return respond(
                    'Eklenti dosyası doğrulanırken bir hata oluştu!.',
                    201
                );
            }
            $zipFile =
                '/tmp/ext-' .
                basename(
                    (string) request()->file('extension')->path()
                );
        } else {
            if (! request()->has('force')) {
                return respond(
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

        return respond('Eklenti Başarıyla yüklendi.', 200);
    }

    /**
     * Setup new extension
     *
     * This function handles extension setup steps (setting perms etc.)
     *
     * @param $zipFile
     * @param $verify
     * @return array
     * @throws GuzzleException
     */
    public function setupNewExtension($zipFile, $verify = false)
    {
        // Initialize Zip Archive Object to use it later.
        $zip = new ZipArchive();

        // Try to open zip file.
        if (! $zip->open($zipFile)) {
            system_log(7, 'EXTENSION_UPLOAD_FAILED_CORRUPTED');

            return [respond('Eklenti Dosyası Açılamıyor.', 201), null];
        }

        // Determine a random tmp folder to extract files
        $path = '/tmp/' . Str::random();
        // Extract Zip to the Temp Folder.
        try {
            $zip->extractTo($path);
        } catch (\Exception) {
            return [respond('Eklenti Dosyası Açılamıyor.', 201), null];
        }

        if (count(scandir($path)) == 3) {
            $path = $path . '/' . scandir($path)[2];
        }

        // Now that we have everything, let's extract database.
        $file = file_get_contents($path . '/db.json');

        $json = json_decode($file, true);

        preg_match('/[A-Za-z-]+/', (string) $json['name'], $output);
        if (empty($output) || $output[0] != $json['name']) {
            return [respond('Eklenti isminde yalnızca harflere izin verilmektedir.', 201), null];
        }

        if (
            array_key_exists('supportedLiman', $json) &&
            getVersionCode() < intval($json['supportedLiman'])
        ) {
            return [
                respond(
                    __("Bu eklentiyi yükleyebilmek için Liman'ı güncellemelisiniz, gerekli minimum liman sürüm kodu") . ' ' .
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

                return [respond('Eklentinin bu sürümü zaten yüklü', 201), null];
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

        $passPath = '/liman/keys' . DIRECTORY_SEPARATOR . $new->id;

        Command::runSystem('chmod 760 @{:path}', [
            'path' => $passPath,
        ]);

        file_put_contents($passPath, Str::random(32));

        $extension_folder = '/liman/extensions/' . strtolower((string) $json['name']);

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

    /**
     * Create new extension on Liman
     *
     * @return JsonResponse|Response
     * @throws GuzzleException
     */
    public function newExtension()
    {
        $name = trim((string) request('name'));
        $folder = '/liman/extensions/' . strtolower($name);

        preg_match('/[A-Za-z-]+/', (string) request('name'), $output);
        if (empty($output) || $output[0] != $name) {
            return respond(
                'Eklenti isminde yalnızca harflere izin verilmektedir.',
                201
            );
        }

        if (Extension::where('name', request('name'))->exists()) {
            return respond('Bu isimle zaten bir eklenti var.', 201);
        }

        if (! in_array(request('template'), array_keys((array) fetchExtensionTemplates()->templates))) {
            return respond('Lütfen geçerli bir tip seçiniz.', 201);
        }

        $template = request('template');
        $template_folder = storage_path('extension_templates/' . $template . '/');
        Command::runLiman('cp -r @{:template_folder} @{:folder}', [
            'template_folder' => $template_folder,
            'folder' => $folder,
        ]);

        foreach (glob("$folder/*.json") as $file) {
            $content = file_get_contents($file);
            $content = str_replace([
                '<NAME>',
                '<PUBLISHER>',
                '<SUPPORTED_LIMAN>',
                '<SUPPORT>',
            ], [
                request('name'),
                auth()->user()->name,
                trim(file_get_contents(storage_path('VERSION'))),
                auth()->user()->email,
            ], $content);
            file_put_contents($file, $content);
        }

        $json = json_decode(file_get_contents("$folder/db.json"));
        $ext = Extension::create([
            'name' => request('name'),
            'version' => '0.0.1',
            'icon' => '',
            'service' => '',
            'language' => $json->language,
        ]);

        $system = rootSystem();

        $system->userAdd($ext->id);

        $passPath = '/liman/keys' . DIRECTORY_SEPARATOR . $ext->id;

        Command::runSystem('chmod 760 @{:path}', [
            'path' => $passPath,
        ]);

        file_put_contents($passPath, Str::random(32));

        request()->request->add(['server' => 'none']);
        request()->request->add(['extension_id' => $ext->id]);

        $system->fixExtensionPermissions($ext->id, $ext->name);

        system_log(6, 'EXTENSION_CREATE', [
            'extension_id' => $ext->id,
        ]);

        return respond(route('extension_one', $ext->id), 300);
    }

    /**
     * Update extension order
     *
     * @return JsonResponse|Response
     */
    public function updateExtOrders()
    {
        foreach (json_decode((string) request('data')) as $extension) {
            Extension::where('id', $extension->id)->update([
                'order' => $extension->order,
            ]);
        }

        return respond('Sıralamalar güncellendi', 200);
    }

    /**
     * Auto update extension
     *
     * @return JsonResponse|Response
     */
    public function autoUpdateExtension()
    {
        $json = json_decode(
            file_get_contents(storage_path('extension_updates')),
            true
        );
        $collection = collect($json);
        $obj = $collection
            ->where('extension_id', request('extension_id'))
            ->first();

        if (! $obj) {
            return respond('Eklenti Bulunamadı', 201);
        }

        $job = (new ExtensionUpdaterJob(
            request('extension_id'),
            $obj['versionCode'],
            $obj['downloadLink'],
            $obj['hashSHA512'],
            true
        ))->onQueue('system_updater');

        // Dispatch job right away.
        $job_id = app(Dispatcher::class)->dispatch($job);

        return respond(
            'Talebiniz başarıyla alındı, eklenti güncellendiğinde bildirim alacaksınız.'
        );
    }

    /**
     * Get extension access logs
     *
     * You can request this function from a remote API with an access token
     * Needed form values:
     *  - extension_id (*)
     *  - query
     *  - page
     *  - log_user_id
     *  - count
     *  - liman-token
     *
     * @return JsonResponse|Response
     */
    public function accessLogs()
    {
        if (! Permission::can(user()->id, 'liman', 'id', 'view_logs')) {
            return respond(
                'Eklenti Günlük Kayıtlarını görüntülemek için yetkiniz yok',
                201
            );
        }

        $page = request('page') * 10;
        $query = request('query') ? request('query') : '';
        $count = intval(
            Command::runLiman(
                'cat /liman/logs/liman_new.log | grep @{:user_id} | grep @{:extension_id} | grep @{:query} | grep -v "recover middleware catch" | wc -l',
                [
                    'query' => $query,
                    'user_id' => strlen(request('log_user_id')) > 5 ? request('log_user_id') : '',
                    'extension_id' => request('extension_id'),
                ]
            )
        );
        $head = $page > $count ? $count % 10 : 10;
        $data = Command::runLiman(
            'cat /liman/logs/liman_new.log | grep @{:user_id} | grep @{:extension_id} | grep @{:query} | grep -v "recover middleware catch" | tail -{:page} | head -{:head} | tac',
            [
                'query' => $query,
                'page' => $page,
                'head' => $head,
                'user_id' => strlen(request('log_user_id')) > 5 ? request('log_user_id') : '',
                'extension_id' => request('extension_id'),
            ]
        );
        $clean = [];

        $knownUsers = [];
        $knownExtensions = [];

        if ($data == '') {
            return response()->json([
                'current_page' => request('page'),
                'count' => request('count'),
                'total_records' => $count,
                'records' => [],
            ]);
        }

        foreach (explode("\n", (string) $data) as $row) {
            $row = json_decode($row);

            if (isset($row->request_details->extension_id)) {
                if (! isset($knownExtensions[$row->request_details->extension_id])) {

                    $extension = Extension::find($row->request_details->extension_id);
                    if ($extension) {
                        $knownExtensions[$row->request_details->extension_id] =
                            $extension->display_name;
                    } else {
                        $knownExtensions[$row->request_details->extension_id] =
                            $row->request_details->extension_id;
                    }
                }
                $row->extension_id = $knownExtensions[$row->request_details->extension_id];
            } else {
                $row->extension_id = __('Komut');
            }

            if (! isset($knownUsers[$row->user_id])) {
                $user = User::find($row->user_id);
                if ($user) {
                    $knownUsers[$row->user_id] = $user->name;
                } else {
                    $knownUsers[$row->user_id] = $row->user_id;
                }
            }

            $row->user_id = $knownUsers[$row->user_id];

            if (isset($row->request_details->lmntargetFunction)) {
                $row->view = $row->request_details->lmntargetFunction;

                if (isset($row->request_details->lmntargetFunction) && $row->request_details->lmntargetFunction == '') {
                    if ($row->lmn_level == 'high_level' && isset($row->request_details->title)) {
                        $row->view = base64_decode($row->request_details->title);
                    }
                }
            } else {
                $row->view = __('Komut');
            }

            array_push($clean, $row);
        }

        return response()->json([
            'current_page' => request('page'),
            'count' => request('count'),
            'total_records' => $count,
            'records' => $clean,
        ]);
    }
}
