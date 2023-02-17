<?php

namespace App\Http\Controllers\Extension\Sandbox;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Server;
use App\Models\ServerKey;
use App\Models\Token;
use App\Models\UserSettings;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

/**
 * Extension Sandbox Controller
 *
 * @extends Controller
 */
class MainController extends Controller
{
    private $extension;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->initializeClass();

            return $next($request);
        });
    }

    /**
     * Constructive events for extension
     *
     * @return void
     */
    public function initializeClass()
    {
        $this->extension = getExtensionJson(extension()->name);

        $this->checkForMissingSettings();

        $this->checkPermissions();
    }

    /**
     * Check if existing settings is valid for extension
     *
     * @return void
     */
    private function checkForMissingSettings()
    {
        $key = ServerKey::where([
            'server_id' => server()->id,
            'user_id' => user()->id,
        ])->first();
        $extra = [];
        if ($key) {
            $extra = ['clientUsername', 'clientPassword'];
        }
        foreach ($this->extension['database'] as $setting) {
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
                system_log(7, 'EXTENSION_MISSING_SETTINGS', [
                    'extension_id' => extension()->id,
                ]);
                redirect_now(
                    route('extension_server_settings_page', [
                        'server_id' => server()->id,
                        'extension_id' => extension()->id,
                    ])
                );
            }
        }
    }

    /**
     * Control if user is eligible to use this extension
     *
     * @return bool
     */
    private function checkPermissions(): bool
    {
        if (
            ! Permission::can(
                auth()->id(),
                'function',
                'name',
                strtolower((string) extension()->name),
                request('function_name')
            )
        ) {
            system_log(7, 'EXTENSION_NO_PERMISSION', [
                'extension_id' => extension()->id,
                'target_name' => request('function_name'),
            ]);
            $function = request('function_name');
            $extensionJson = json_decode(
                file_get_contents(
                    '/liman/extensions/' .
                    strtolower((string) extension()->name) .
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
                ->where('name', request('function_name'))
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
                    strtolower((string) extension()->name),
                    $function
                )
            ) {
                abort(403, $function . ' için yetkiniz yok.');
            }
        }

        return true;
    }

    /**
     * Calls extension from render engine
     *
     * This function renders extension files in a isolated space from Liman.
     * This wrapper handles necessary events to run an extension.
     *
     * @return Application|Factory|View|JsonResponse|Response|never
     * @throws GuzzleException
     */
    public function API()
    {
        if (extension()->status == '0') {
            return respond(
                __('Eklenti şu an güncelleniyor, lütfen birazdan tekrar deneyin.'),
                201
            );
        }

        if (extension()->require_key == 'true' && server()->key() == null) {
            return respond(
                __('Bu eklentiyi kullanabilmek için bir anahtara ihtiyacınız var, lütfen kasa üzerinden bir anahtar ekleyin.'),
                403
            );
        }

        $page = request('target_function')
            ? request('target_function')
            : 'index';
        $view = 'extension_pages.server';

        $token = Token::create(user()->id);

        $dbJson = getExtensionJson(extension()->name);
        if (isset($dbJson['preload']) && $dbJson['preload']) {
            $client = new Client(['verify' => false]);
            try {
                $res = $client->request('POST', env('RENDER_ENGINE_ADDRESS', 'https://127.0.0.1:2806'), [
                    'form_params' => [
                        'lmntargetFunction' => $page,
                        'extension_id' => extension()->id,
                        'server_id' => server()->id,
                        'token' => $token,
                    ],
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
                if (env('APP_DEBUG', false)) {
                    return abort(
                        504,
                        __('Liman render service is not working or crashed. ') . $e->getMessage(),
                    );
                } else {
                    return abort(
                        504,
                        __('Liman render service is not working or crashed.'),
                    );
                }
            }
        }

        return view($view, [
            'auth_token' => $token,
            'tokens' => user()
                ->accessTokens()
                ->get()
                ->toArray(),
            'last' => $this->getNavigationServers(),
            'extContent' => isset($output) ? $output : null,
        ]);
    }

    /**
     * Get navigation servers
     *
     * @return array
     */
    private function getNavigationServers()
    {
        $navServers = DB::table('server_groups')
            ->where('servers', 'like', '%' . server()->id . '%')
            ->get();
        $cleanServers = [];
        foreach ($navServers as $rawServers) {
            $servers = explode(',', (string) $rawServers->servers);
            foreach ($servers as $server) {
                if (Permission::can(user()->id, 'server', 'id', $server)) {
                    array_push($cleanServers, $server);
                }
            }
        }

        $cleanServers = array_unique($cleanServers);
        $cleanExtensions = [];

        $serverObjects = Server::find($cleanServers);
        unset($cleanServers);
        foreach ($serverObjects as $server) {
            $cleanExtensions[$server->id . ':' . $server->name] = $server
                ->extensions()
                ->pluck('display_name', 'id')
                ->toArray();
        }
        if (empty($cleanExtensions)) {
            $cleanExtensions[server()->id . ':' . server()->name] = server()
                ->extensions()
                ->pluck('display_name', 'id')
                ->toArray();
        }

        $last = [];

        foreach ($cleanExtensions as $serverobj => $extensions) {
            [$server_id, $server_name] = explode(':', $serverobj);
            foreach ($extensions as $extension_id => $extension_name) {
                $prefix = $extension_id . ':' . $extension_name;
                $current = array_key_exists($prefix, $last)
                    ? $last[$prefix]
                    : [];
                array_push($current, [
                    'id' => $server_id,
                    'name' => $server_name,
                ]);
                $last[$prefix] = $current;
            }
        }

        return $last;
    }
}
