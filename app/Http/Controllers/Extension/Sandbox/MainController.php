<?php

namespace App\Http\Controllers\Extension\Sandbox;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\UserSettings;
use App\Permission;
use App\Server;
use App\ServerLog;
use App\Classes\Sandbox\PHPSandbox;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

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

    public function initializeClass()
    {
        $this->extension = json_decode(
            file_get_contents(
                "/liman/extensions/" .
                    strtolower(extension()->name) .
                    DIRECTORY_SEPARATOR .
                    "db.json"
            ),
            true
        );

        list($result, $redirect) = $this->checkForMissingSettings();
        if (!$result) {
            return $redirect;
        }

        $this->checkPermissions();
        $this->sandbox = sandbox();
    }

    public function API()
    {
        $page = request('target_function')
            ? request('target_function')
            : 'index';

        $logObject = ServerLog::new(extension()->name, $page);
        
        $this->sandbox->setLogId($logObject->id);

        list($output, $timestamp) = $this->executeSandbox($page);

        system_log(7, "EXTENSION_RENDER_PAGE", [
            "extension_id" => extension()->id,
            "server_id" => server()->id,
            "view" => $page,
            "log_id" => $logObject->id
        ]);
        if (trim($output) == "") {
            abort(504, "İstek zaman aşımına uğradı!");
        }
        if (request()->wantsJson()) {
            $code = 200;
            try {
                $json = json_decode($output, true);
                if (array_key_exists("status", $json)) {
                    $code = intval($json["status"]);
                }
            } catch (\Exception $exception) {
            }
            if (isJson($output)) {
                return response()->json(json_decode($output), $code);
            }
            return response($output, $code);
        } else {
            // Let's check output is json or not.
            $json = json_decode($output, true);
            if (json_last_error() == JSON_ERROR_NONE && is_array($json)) {
                $output = view('l.alert', [
                    "title" => extension()->name,
                    "message" => array_key_exists("message", $json)
                        ? $json["message"]
                        : "Bilinmeyen bir hata oluştu, lütfen eklenti geliştiricisi ile iletişime geçiniz.",
                    "type" =>
                        array_key_exists("status", $json) &&
                        intval($json["status"]) > 200
                            ? "danger"
                            : "info",
                ]);
            }

            if (
                env('LIMAN_RESTRICTED') == true &&
                !user()->isAdmin()
            ) {
                return view('extension_pages.server_restricted', [
                    "view" => $output,
                ]);
            }
            return view('extension_pages.server', [
                "viewName" => "",
                "view" => $output,
                "last" => $this->getNavigationServers(),
            ]);
        }
    }

    private function checkForMissingSettings()
    {
        foreach ($this->extension["database"] as $setting) {
            if (isset($setting["required"]) && $setting["required"] === false) {
                continue;
            }
            if (
                !UserSettings::where([
                    "user_id" => user()->id,
                    "server_id" => server()->id,
                    "name" => $setting["variable"],
                ])->exists()
            ) {
                system_log(7, "EXTENSION_MISSING_SETTINGS", [
                    "extension_id" => extension()->id,
                ]);
                redirect_now(
                    route('extension_server_settings_page', [
                        "server_id" => server()->id,
                        "extension_id" => extension()->id,
                    ])
                );
            }
        }
        return [true, null];
    }

    private function checkPermissions()
    {
        if (
            !Permission::can(
                auth()->id(),
                "function",
                "name",
                strtolower(extension()->name),
                request('function_name')
            )
        ) {
            system_log(7, "EXTENSION_NO_PERMISSION", [
                "extension_id" => extension()->id,
                "target_name" => request('function_name'),
            ]);
            $function = request("function_name");
            $extensionJson = json_decode(
                file_get_contents(
                    "/liman/extensions/" .
                        strtolower(extension()->name) .
                        DIRECTORY_SEPARATOR .
                        "db.json"
                ),
                true
            );

            $functions = collect([]);

            if (array_key_exists("functions", $extensionJson)) {
                $functions = collect($extensionJson["functions"]);
            }

            $isActive = "false";
            $functionOptions = $functions
                ->where('name', request("function_name"))
                ->first();
            if ($functionOptions) {
                $isActive = $functionOptions["isActive"];
            }
            if (
                $isActive == "true" &&
                !Permission::can(
                    user()->id,
                    "function",
                    "name",
                    strtolower(extension()->name),
                    $function
                )
            ) {
                abort(403, $function . " için yetkiniz yok.");
            }
        }
        return true;
    }

    private function executeSandbox($function)
    {
        if (!isset($this->sandbox)) {
            $this->initializeClass();
        }
        $command = $this->sandbox->command($function);

        $before = Carbon::now();
        $output = shell_exec($command);
        return [$output, $before->diffInMilliseconds(Carbon::now()) / 1000];
    }

    private function getNavigationServers()
    {
        $navServers = DB::select(
            "SELECT * FROM \"server_groups\" WHERE \"servers\" LIKE \"%" .
                server()->id .
                "%\""
        );
        $cleanServers = [];
        foreach ($navServers as $rawServers) {
            $servers = explode(",", $rawServers->servers);
            foreach ($servers as $server) {
                if (Permission::can(user()->id, "server", "id", $server)) {
                    array_push($cleanServers, $server);
                }
            }
        }

        $cleanServers = array_unique($cleanServers);
        $cleanExtensions = [];

        $serverObjects = Server::find($cleanServers);
        unset($cleanServers);
        foreach ($serverObjects as $server) {
            $cleanExtensions[$server->id . ":" . $server->name] = $server
                ->extensions()
                ->pluck('display_name', 'id')
                ->toArray();
        }
        if (empty($cleanExtensions)) {
            $cleanExtensions[server()->id . ":" . server()->name] = server()
                ->extensions()
                ->pluck('display_name', 'id')
                ->toArray();
        }

        $last = [];

        foreach ($cleanExtensions as $serverobj => $extensions) {
            list($server_id, $server_name) = explode(":", $serverobj);
            foreach ($extensions as $extension_id => $extension_name) {
                $prefix = $extension_id . ":" . $extension_name;
                $current = array_key_exists($prefix, $last)
                    ? $last[$prefix]
                    : [];
                array_push($current, [
                    "id" => $server_id,
                    "name" => $server_name,
                ]);
                $last[$prefix] = $current;
            }
        }

        return $last;
    }
}
