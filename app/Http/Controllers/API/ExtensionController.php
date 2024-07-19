<?php

namespace App\Http\Controllers\API;

use App\Exceptions\JsonResponseException;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Extension;
use App\Models\Permission;
use App\Models\Server;
use App\Models\ServerKey;
use App\Models\UserExtensionUsageStats;
use App\Models\UserSettings;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\MimeType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Extension Controller
 *
 * Does jobs about extensions
 */
class ExtensionController extends Controller
{
    /**
     * Extension list
     *
     * @return mixed
     */
    public function index()
    {
        // If server_id exists, don't return extensions that assigned to that server
        // For listing purposes
        $server_id = request('server_id');

        if ($server_id) {
            $extensions = Extension::whereDoesntHave('servers', function ($query) use ($server_id) {
                $query->where('server_id', $server_id);
            })->orderBy('updated_at', 'DESC')->get()->filter(function ($extension) {
                return Permission::can(auth('api')->user()->id, 'extension', 'id', $extension->id);
            })->values();
        } else {
            $extensions = Extension::orderBy('updated_at', 'DESC')->get()->filter(function ($extension) {
                return Permission::can(auth('api')->user()->id, 'extension', 'id', $extension->id);
            })->values();
        }

        return $extensions;
    }

    /**
     * Assign extension to server
     *
     * @return JsonResponse
     */
    public function assign(Request $request)
    {
        try {
            DB::table('server_extensions')->insert(
                array_map(function ($extension) use ($request) {
                    return [
                        'id' => Str::uuid()->toString(),
                        'server_id' => $request->server_id,
                        'extension_id' => $extension,
                    ];
                }, $request->extensions)
            );
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'An error occured while assigning server.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $server = Server::find($request->server_id);
        $extensions = Extension::whereIn('id', $request->extensions)->get();
        foreach ($extensions as $extension) {
            AuditLog::write(
                'extension',
                'assign',
                [
                    'extension_id' => $extension->id,
                    'extension_name' => $extension->name,
                    'server_id' => $request->server_id,
                    'server_name' => $server->name,
                ],
                "EXTENSION_ASSIGNED_TO_SERVER"
            );
        }

        return response()->json([
            'message' => 'Assigned successfully.',
        ]);
    }

    /**
     * Unassign extensions from server
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function unassign(Request $request)
    {
        try {
            DB::table('server_extensions')
                ->where('server_id', $request->server_id)
                ->whereIn('extension_id', $request->extensions)
                ->delete();
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'An error occured while unassigning server.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $server = Server::find($request->server_id);
        $extensions = Extension::whereIn('id', $request->extensions)->get();
        foreach ($extensions as $extension) {
            AuditLog::write(
                'extension',
                'assign',
                [
                    'extension_id' => $extension->id,
                    'extension_name' => $extension->name,
                    'server_id' => $request->server_id,
                    'server_name' => $server->name,
                ],
                "EXTENSION_UNASSIGNED_FROM_SERVER"
            );
        }

        return response()->json([
            'message' => 'Unassigned successfully.',
        ]);
    }

    /**
     * Render PHP from Sandbox and return it
     *
     * @param Request $request
     * @return JsonResponse|Response
     * @throws GuzzleException
     */
    public function render(Request $request)
    {
        $dbJson = getExtensionJson(extension()->name);

        $this->checkPermissions(extension());
        $this->checkForMissingSettings($dbJson);

        if (extension()->status == '0') {
            return response()->json([
                'message' => 'Eklenti şu anda güncelleniyor, biraz sonra tekrar deneyiniz.',
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        if (extension()->require_key == 'true' && server()->key() == null) {
            return response()->json([
                'message' => 'Bu eklentiyi kullanabilmek için bir anahtara ihtiyacınız var, lütfen kasa üzerinden bir anahtar ekleyin.'
            ], Response::HTTP_FORBIDDEN);
        }

        $page = $request->target_function
            ?: 'index';
        $view = 'extension_pages.server_json';

        if (isset($dbJson['preload']) && $dbJson['preload']) {
            $client = new Client(['verify' => false, 'cookies' => true]);
            try {
                $res = $client->request('POST', env('RENDER_ENGINE_ADDRESS', 'https://127.0.0.1:2806'), [
                    'form_params' => [
                        'lmntargetFunction' => $page,
                        'extension_id' => extension()->id,
                        'server_id' => server()->id,
                        'locale' => app()->getLocale(),
                    ],
                    'cookies' => convertToCookieJar($request, "127.0.0.1"),
                    'timeout' => 30,
                ]);
                $output = (string) $res->getBody();

                $isJson = isJson($output, true);
                if ($isJson && isset($isJson->status) && $isJson->status != 200) {
                    return respond(
                        $isJson->message,
                        $isJson->status,
                    );
                }
            } catch (\Exception $e) {
                return response()->json([
                    'message' => __('Liman render service is not working or crashed. '). !env('APP_DEBUG', false) ?: $e->getMessage()
                ], Response::HTTP_GATEWAY_TIMEOUT);
            }
        }

        try {
            $stat = UserExtensionUsageStats::firstOrCreate([
                'user_id' => auth('api')->user()->id,
                'extension_id' => $request->extension_id,
                'server_id' => $request->server_id,
            ]);

            $stat->increment('usage');
        } catch (\Throwable $e) {
            // Do nothing on errors as it's not too important to care about.
        }

        return response()->json(
            [
                'extension_name' => extension()->display_name,
                'server_name' => server()->name,
                'html' => trim(
                    view($view, [
                        'tokens' => user()
                            ->accessTokens()
                            ->get()
                            ->toArray(),
                        'extContent' => $output ?? null,
                        'dbJson' => $dbJson,
                    ])->render()
                ),
            ]
        );
    }

    /**
     * Check if existing settings is valid for extension
     *
     * @return void
     */
    private function checkForMissingSettings($extension)
    {
        $key = ServerKey::where([
            'server_id' => server()->id,
            'user_id' => user()->id,
        ])->first();
        $extra = [];
        if ($key) {
            $extra = ['clientUsername', 'clientPassword'];
        }
        if (auth()->user()->auth_type == 'ldap') {
            $extensionJson = getExtensionJson($extension['name']);

            if (isset($extensionJson['ldap_support_fields']))
                $extra = array_merge($extra, array_values($extensionJson['ldap_support_fields']));
        }
        foreach ($extension['database'] as $setting) {
            if (
                in_array($setting['variable'], $extra)
            ) {
                continue;            
            }

            if (isset($setting['required']) && $setting['required'] === false) {
                continue;
            }
            $opts = [
                'server_id' => server()->id,
                'name' => $setting['variable'],
            ];

            if (! isset($setting['global']) || $setting['global'] === false) {
                $opts['user_id'] = user()->id;
            }

            if (
                ! in_array($setting['variable'], $extra) &&
                ! UserSettings::where($opts)->exists()
            ) {
                throw new JsonResponseException(['redirect' => true], null, Response::HTTP_NOT_ACCEPTABLE);
            }
        }
    }

    /**
     * Control if user is eligible to use this extension
     *
     * @return void
     */
    private function checkPermissions($extension)
    {
        if (
            ! Permission::can(
                auth()->id(),
                'function',
                'name',
                strtolower((string) $extension->name),
                request('target_function')
            )
        ) {
            $function = request('target_function');
            $extensionJson = json_decode(
                file_get_contents(
                    '/liman/extensions/' .
                    strtolower((string) $extension->name) .
                    DIRECTORY_SEPARATOR .
                    'db.json'
                ),
                true
            );

            $functions = collect([]);

            if (array_key_exists('functions', $extensionJson)) {
                $functions = collect($extensionJson['functions']);
            }

            $isActive = 'false';
            $functionOptions = $functions
                ->where('name', request('target_function'))
                ->first();
            if ($functionOptions) {
                $isActive = $functionOptions['isActive'];
            }
            if (
                $isActive == 'true' &&
                ! Permission::can(
                    user()->id,
                    'function',
                    'name',
                    strtolower((string) $extension->name),
                    $function
                )
            ) {
                throw new JsonResponseException(['message' => 'Bu işlem için yetkiniz bulunmamaktadır.'], null, Response::HTTP_FORBIDDEN);
            }
        }
    }

    /**
     * Get files from extensions public folder
     *
     * @return BinaryFileResponse|void
     */
    public function publicFolder()
    {
        $basePath =
            '/liman/extensions/' . strtolower((string) extension()->name) . '/public/';

        $targetPath = $basePath . explode('public/', (string) url()->current(), 2)[1];

        if (realpath($targetPath) != $targetPath) {
            abort(404);
        }

        if (is_file($targetPath)) {
            return response()->download($targetPath, null, [
                'Content-Type' => MimeType::fromExtension(pathinfo($targetPath, PATHINFO_EXTENSION)),
            ]);
        } else {
            abort(404);
        }
    }
}
