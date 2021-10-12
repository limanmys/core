<?php

use App\Models\AdminNotification;
use App\Models\Extension;
use App\Models\Notification;
use App\Models\SystemSettings;
use App\Models\Permission;
use App\Models\Server;
use App\Models\Certificate;
use App\Models\Liman;
use App\System\Command;
use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Jenssegers\Blade\Blade;
use App\System\Helper;
use mervick\aesEverywhere\AES256;
use phpseclib\Crypt\RSA;
use Illuminate\Support\Facades\Validator;
use Jackiedo\DotenvEditor\Facades\DotenvEditor;

if (!function_exists('validate')) {
	function validate($rules, $messages=[])
	{
		$validator = Validator::make(request()->all(), $rules, $messages);
		if ($validator->fails()) {
			$errors = $validator->errors();
			abort(400, $errors->first());
		}
	}
}

if (!function_exists('updateSystemSettings')) {
    function updateSystemSettings()
    {
        SystemSettings::updateOrCreate(
            ['key' => 'APP_KEY'],
            ['data' => env('APP_KEY')]
        );

        SystemSettings::updateOrCreate(
            ['key' => 'LIMAN_RESTRICTED'],
            ['data' => env('LIMAN_RESTRICTED', false)]
        );

        SystemSettings::updateOrCreate(
            ['key' => 'SSL_PUBLIC_KEY'],
            ['data' => file_get_contents("/liman/certs/liman.crt")]
        );

        SystemSettings::updateOrCreate(
            ['key' => 'SSL_PRIVATE_KEY'],
            ['data' => file_get_contents("/liman/certs/liman.key")]
        );
        $sshPublic = SystemSettings::where([
            "key" => "SSH_PUBLIC",
        ])->first();
        if (!$sshPublic) {
            $rsa = new RSA();
            $rsa->setPublicKeyFormat(RSA::PUBLIC_FORMAT_OPENSSH);
            extract($rsa->createKey());
            `mkdir -p /home/liman/.ssh`;
            file_put_contents("/home/liman/.ssh/authorized_keys", $publickey);
            file_put_contents("/home/liman/.ssh/liman_pub", $publickey);
            file_put_contents("/home/liman/.ssh/liman_priv", $privatekey);

            chmod("/home/liman/.ssh/liman_pub", 0600);
            chmod("/home/liman/.ssh/liman_priv", 0600);

            SystemSettings::create([
                "key" => "SSH_PUBLIC",
                "data" => $publickey,
            ]);

            SystemSettings::updateOrCreate(
                ['key' => 'SSH_PRIVATE_KEY'],
                ['data' => $privatekey]
            );
        }
    }
}

if (!function_exists('receiveSystemSettings')) {
    function receiveSystemSettings()
    {
        $app_key = SystemSettings::where([
            "key" => "APP_KEY",
        ])->first();

        if ($app_key) {
            setEnv([
                "APP_KEY" => $app_key->data,
            ]);
        }

        $restricted = SystemSettings::where([
            "key" => "LIMAN_RESTRICTED",
        ])->first();

        if ($restricted) {
            setEnv([
                "LIMAN_RESTRICTED" => $restricted->data,
            ]);
        }

        $public_key = SystemSettings::where([
            "key" => "SSL_PUBLIC_KEY",
        ])->first();

        if ($public_key) {
            file_put_contents("/liman/certs/liman.crt", $public_key->data);
        }

        $private_key = SystemSettings::where([
            "key" => "SSL_PRIVATE_KEY",
        ])->first();

        if ($private_key) {
            file_put_contents("/liman/certs/liman.key", $private_key->data);
        }

        $sshPublic = SystemSettings::where([
            "key" => "SSH_PUBLIC",
        ])->first();

        if ($sshPublic) {
            `mkdir -p /home/liman/.ssh`;
            file_put_contents(
                "/home/liman/.ssh/authorized_keys",
                $sshPublic->data
            );
            file_put_contents("/home/liman/.ssh/liman_pub", $sshPublic->data);
            chmod("/home/liman/.ssh/liman_pub", 0600);
        }

        $sshPrivate = SystemSettings::where([
            "key" => "SSH_PRIVATE_KEY",
        ])->first();

        if ($sshPrivate) {
            `mkdir -p /home/liman/.ssh`;
            file_put_contents("/home/liman/.ssh/liman_priv", $sshPrivate->data);
            chmod("/home/liman/.ssh/liman_priv", 0600);
        }
    }
}

if (!function_exists('respond')) {
    /**
     * @param $message
     * @param int $status
     * @return JsonResponse|Response
     */
    function respond($message, $status = 200)
    {
        if (request()->wantsJson()) {
            return response()->json(
                [
                    "message" => is_array($message) ? $message : __($message),
                    "status" => $status,
                ],
                $status
            );
        } else {
            return response()->view(
                'general.error',
                [
                    "message" => __($message),
                    "status" => $status,
                ],
                $status
            );
        }
    }
}

if (!function_exists('syncFiles')) {
    function syncFiles()
    {
        $masterIp = env('LIMAN_MASTER_IP');

        if ($masterIp == "") {
            $firstLiman = Liman::first();
            if ($firstLiman == null) {
                return;
            }
            $masterIp = $firstLiman->last_ip;
        }

        Command::runLiman(
            "rsync -Pav -e \"ssh -i /home/liman/.ssh/liman_priv -o 'StrictHostKeyChecking no'\" liman@" .
                "$masterIp" .
                ":/liman/extensions/ /liman/extensions/"
        );
        Command::runLiman(
            "rsync -Pav -e \"ssh -i /home/liman/.ssh/liman_priv -o 'StrictHostKeyChecking no'\" --exclude 'service.key' liman@" .
                "$masterIp" .
                ":/liman/keys/ /liman/keys/"
        );
        Command::runLiman(
            "rsync -Pav -e \"ssh -i /home/liman/.ssh/liman_priv -o 'StrictHostKeyChecking no'\" liman@" .
                "$masterIp" .
                ":/liman/modules/ /liman/modules/"
        );

        $root = rootSystem();
        $extensions = Extension::all();
        $names = [];

        foreach ($extensions as $extension) {
            array_push($names, strtolower($extension->name));
            $root->userAdd($extension->id);
            $root->fixExtensionPermissions($extension->id, $extension->name);
            $json = getExtensionJson($extension->name);
            if (
                array_key_exists("dependencies", $json) &&
                $json["dependencies"] != ""
            ) {
                $root->installPackages($json["dependencies"]);
            }
        }

        $scan = scandir('/liman/extensions/');

        foreach ($scan as $a) {
            if (substr($a, 0, 1) == ".") {
                continue;
            }
            if (!in_array($a, $names)) {
                Command::runLiman("rm -rf '/liman/extensions/{:a}'", [
                    'a' => $a
                ]);
            }
        }

        $dns = SystemSettings::where([
            "key" => "SYSTEM_DNS",
        ])->first();
        if ($dns) {
            $json = json_decode($dns->data);
            $root->dnsUpdate($json[0], $json[1], $json[2]);
        }

        $certificates = SystemSettings::where([
            "key" => "SYSTEM_CERTIFICATES",
        ])->first();
        if ($certificates) {
            $json = json_decode($certificates->data, true);
            foreach ($json as $cert) {
                if (
                    is_file(
                        "/usr/local/share/ca-certificates/" .
                            $cert["targetName"] .
                            ".crt"
                    )
                ) {
                    continue;
                }
                $root->addCertificate(
                    $cert["certificate"],
                    $cert["targetName"]
                );
            }
        }
    }
}

if (!function_exists('ip_in_range')) {
    function ip_in_range($ip, $range)
    {
        if (strpos($range, '/') == false) {
            $range .= '/32';
        }
        // $range is in IP/CIDR format eg 127.0.0.1/24
        list($range, $netmask) = explode('/', $range, 2);
        $range_decimal = ip2long($range);
        $ip_decimal = ip2long($ip);
        $wildcard_decimal = pow(2, 32 - $netmask) - 1;
        $netmask_decimal = ~$wildcard_decimal;
        return ($ip_decimal & $netmask_decimal) ==
            ($range_decimal & $netmask_decimal);
    }
}

if (!function_exists('strposX')) {
    function strposX($haystack, $needle, $number)
    {
        if ($number == '1') {
            return strpos($haystack, $needle);
        } elseif ($number > '1') {
            return strpos(
                $haystack,
                $needle,
                strposX($haystack, $needle, $number - 1) + strlen($needle)
            );
        } else {
            return error_log(
                'Error: Value for parameter $number is out of range'
            );
        }
    }
}

if (!function_exists('rootSystem')) {
    function rootSystem()
    {
        return new Helper();
    }
}

if (!function_exists('users')) {
    function users()
    {
        return User::all();
    }
}

if (!function_exists('registerModuleRoutes')) {
    function registerModuleRoutes()
    {
        $files = searchModuleFiles('routes.php');
        foreach ($files as $file) {
            require_once $file . "/routes.php";
        }
    }
}

if (!function_exists('registerModuleListeners')) {
    function registerModuleListeners()
    {
        $files = searchModuleFiles('listeners.php');
        foreach ($files as $file) {
            require_once $file . "/listeners.php";
        }
    }
}

if (!function_exists('searchModuleFiles')) {
    function searchModuleFiles($type)
    {
        $command = "find /liman/modules/ -name @{:type}";

        $output = Command::runLiman($command, [
            'type' => $type
        ]);
        if ($output == "") {
            return [];
        }

        $data = explode("\n", $output);
        $arr = [];
        foreach ($data as $file) {
            array_push($arr, dirname($file));
        }
        return $arr;
    }
}

if (!function_exists('getLimanPermissions')) {
    /**
     * @return mixed
     */
    function getLimanPermissions($user_id)
    {
        $map = [
            "view_logs" => "Sunucu Günlük Kayıtlarını Görüntüleme",
            "add_server" => "Sunucu Ekleme",
            "server_services" => "Sunucu Servislerini Görüntüleme",
        ];
        $permissions = Permission::where([
            "morph_id" => $user_id ? $user_id : user()->id,
            "type" => "liman",
            "key" => "id",
        ])->get();
        $permissions = $permissions->map(function ($permission) use (&$map) {
            $permission->name = __($map[$permission->value]);
            $permission->id = $permission->value;
            return $permission;
        });
        return $permissions;
    }
}

if (!function_exists('settingsModuleViews')) {
    /**
     * @return mixed
     */
    function settingsModuleViews()
    {
        $str = "";
        foreach (searchModuleFiles('settings.blade.php') as $file) {
            $blade = new Blade(
                [realpath(base_path('resources/views/components')), $file],
                "/tmp"
            );
            $str .= $blade->render('settings');
        }
        return $str;
    }
}

if (!function_exists('renderModuleView')) {
    /**
     * @return mixed
     */
    function renderModuleView($module, $page, $params = [])
    {
        $blade = new Blade('/liman/modules/' . $module . '/views/', "/tmp");
        $str = $blade->render($page, $params);
        return view('modules.layout', [
            "name" => $module,
            "html" => $str,
        ]);
    }
}

$sidebarModuleLinks = null;
if (!function_exists('sidebarModuleLinks')) {
    /**
     * @return mixed
     */
    function sidebarModuleLinks()
    {
        global $sidebarModuleLinks;
        if ($sidebarModuleLinks != null) {
            return $sidebarModuleLinks;
        }
        $array = [];
        foreach (searchModuleFiles('sidebar.json') as $file) {
            $filePath = $file . "/sidebar.json";
            $data = file_get_contents($filePath);
            $json = json_decode($data, true);
            if (json_last_error() != JSON_ERROR_NONE) {
                continue;
            }
            foreach ($json as $a) {
                array_push($array, $a);
            }
        }
        $sidebarModuleLinks = $array;
        return $array;
    }
}

if (!function_exists('settingsModuleButtons')) {
    /**
     * @return mixed
     */
    function settingsModuleButtons()
    {
        $str = "";
        foreach (searchModuleFiles('settings.blade.php') as $file) {
            $foo = substr($file, 15);
            $name = substr($foo, 0, strpos($foo, "/"));
            $hrefName = $name;
            if (is_numeric($name[0])) {
                $hrefName = "l-" . $name;
            }

            $str .=
                "<li class=\"nav-item\">
               <a id=\"" .
                $name .
                "tab\" class=\"nav-link\" data-toggle=\"tab\" href=\"#$hrefName\">$name</a>
            </li>";
        }
        return $str;
    }
}

if (!function_exists('getLimanHostname')) {
    function getLimanHostname()
    {
        return trim(`hostname`);
    }
}

if (!function_exists('serverModuleViews')) {
    /**
     * @return mixed
     */
    function serverModuleViews()
    {
        $str = "";
        foreach (searchModuleFiles('server.blade.php') as $file) {
            $blade = new Blade(
                [realpath(base_path('resources/views/components')), $file],
                "/tmp"
            );
            $str .= $blade->render('server');
        }
        return $str;
    }
}

if (!function_exists('serverModuleButtons')) {
    /**
     * @return mixed
     */
    function serverModuleButtons()
    {
        $str = "";
        foreach (searchModuleFiles('server.blade.php') as $file) {
            $foo = substr($file, 15);
            $name = substr($foo, 0, strpos($foo, "/"));
            $str .=
                "<li class=\"nav-item\">
               <a id=\"" .
                $name .
                "tab\"class=\"nav-link\" data-toggle=\"tab\" href=\"#$name\">$name</a>
            </li>";
        }
        return $str;
    }
}

if (!function_exists('getVersion')) {
    /**
     * @return mixed
     */
    function getVersion()
    {
        return file_get_contents(storage_path('VERSION'));
    }
}

if (!function_exists('getVersionCode')) {
    /**
     * @return mixed
     */
    function getVersionCode()
    {
        return intval(file_get_contents(storage_path('VERSION_CODE')));
    }
}

if (!function_exists('notifications')) {
    /**
     * @return mixed
     */
    function notifications()
    {
        return Notification::where([
            "user_id" => auth()->id(),
            "read" => false,
        ])
            ->orderBy('updated_at', 'desc')
            ->get();
    }
}

if (!function_exists('knownPorts')) {
    /**
     * @return mixed
     */
    function knownPorts()
    {
        return ["5986", "636", "443"];
    }
}

if (!function_exists('retrieveCertificate')) {
    /**
     * @return mixed
     */
    function retrieveCertificate($hostname, $port)
    {
        $get = stream_context_create([
            "ssl" => [
                "capture_peer_cert" => true,
                "allow_self_signed" => true,
                "verify_peer" => false,
                "verify_peer_name" => false,
            ],
        ]);
        $flag = false;
        try {
            $read = stream_socket_client(
                "ssl://" . $hostname . ":" . $port,
                $errno,
                $errstr,
                intval(config('liman.server_connection_timeout')),
                STREAM_CLIENT_CONNECT,
                $get
            );
            $flag = true;
        } catch (\Exception $exception) {
        }

        if (!$flag) {
            try {
                $read = stream_socket_client(
                    "tlsv1.1://" . $hostname . ":" . $port,
                    $errno,
                    $errstr,
                    intval(config('liman.server_connection_timeout')),
                    STREAM_CLIENT_CONNECT,
                    $get
                );
                $flag = true;
            } catch (\Exception $exception) {
                return [false, __("Sertifika alınamıyor!")];
            }
        }

        $cert = stream_context_get_params($read);
        $certinfo = openssl_x509_parse(
            $cert['options']['ssl']['peer_certificate']
        );
        openssl_x509_export(
            $cert["options"]["ssl"]["peer_certificate"],
            $publicKey
        );
        $certinfo["subjectKeyIdentifier"] = array_key_exists(
            "subjectKeyIdentifier",
            $certinfo["extensions"]
        )
            ? $certinfo["extensions"]["subjectKeyIdentifier"]
            : "";
        $certinfo["authorityKeyIdentifier"] = array_key_exists(
            "authorityKeyIdentifier",
            $certinfo["extensions"]
        )
            ? substr($certinfo["extensions"]["authorityKeyIdentifier"], 6)
            : "";
        $certinfo["validFrom_time_t"] = Carbon::createFromTimestamp(
            $certinfo["validFrom_time_t"]
        )->format('H:i d/m/Y');
        $certinfo["validTo_time_t"] = Carbon::createFromTimestamp(
            $certinfo["validTo_time_t"]
        )->format('H:i d/m/Y');
        unset($certinfo["extensions"]);
        $path = Str::random(10);
        $certinfo["path"] = $path;
        file_put_contents("/tmp/" . $path, $publicKey);
        return [true, $certinfo];
    }
}

if (!function_exists('adminNotifications')) {
    /**
     * @return mixed
     */
    function adminNotifications()
    {
        return AdminNotification::where([
            "read" => false,
        ])
            ->orderBy('updated_at', 'desc')
            ->get();
    }
}

if (!function_exists('addCertificate')) {
    /**
     * @return mixed
     */
    function addCertificate($hostname, $port, $path)
    {
        rootSystem()->addCertificate(
            '/tmp/' . $path,
            "liman-" . $hostname . "_" . $port
        );

        // Create Certificate Object.
        return Certificate::create([
            "server_hostname" => strtolower($hostname),
            "origin" => $port,
        ]);
    }
}

if (!function_exists('getLimanId')) {
    /**
     * @return mixed
     */
    function getLimanId()
    {
        return md5(
            "l1m@ns3cur1ty" . trim(shell_exec("ls /dev/disk/by-uuid -1"))
        ) . PHP_EOL;
    }
}

if (!function_exists('system_log')) {
    /**
     * @param $level
     * @param $message
     * @param array $array
     */
    function system_log($level, $message, $array = [])
    {
        $array["user_id"] = user() ? user()->id : "";
        $array["ip_address"] = request()->ip();

        switch ($level) {
            case 1:
                Log::emergency($message, $array);
                break;
            case 2:
                Log::alert($message, $array);
                break;
            case 3:
                Log::critical($message, $array);
                break;
            case 4:
                Log::error($message, $array);
                break;
            case 5:
                Log::warning($message, $array);
                break;
            case 6:
                Log::notice($message, $array);
                break;
            case 7:
                Log::info($message, $array);
                break;
            default:
                Log::debug($message, $array);
                break;
        }
    }
}

if (!function_exists('server')) {
    /**
     * @return \App\Models\Server
     */
    function server()
    {
        if (!request('server')) {
            abort(504, "Sunucu Bulunamadı");
        }
        return request('server');
    }
}

if (!function_exists('servers')) {
    /**
     * @return Server|Builder
     */
    function servers()
    {
        return auth()
            ->user()
            ->servers();
    }
}

if (!function_exists('extensions')) {
    /**
     * @param array $filter
     * @return array
     */
    function extensions($filter = [])
    {
        return Extension::getAll($filter);
    }
}

if (!function_exists('extension')) {
    /**
     * @param null $id
     * @return Extension
     */
    function extension($id = null)
    {
        if ($id == null) {
            $id = request('extension_id');
        }
        return Extension::one($id);
    }
}

if (!function_exists('user')) {
    /**
     * @param null $id
     * @return User
     */
    function user()
    {
        return auth()->user();
    }
}

if (!function_exists('sandbox')) {
    /**
     * @param null $id
     * @return App\Sandboxes\Sandbox
     */
    function sandbox($language = null)
    {
        if ($language == null) {
            $language = extension()->language;
        }
        switch ($language) {
            case "python":
                return new App\Sandboxes\PythonSandbox();
                break;
            case "php":
            default:
                return new App\Sandboxes\PHPSandbox();
                break;
        }
    }
}

if (!function_exists('hook')) {
    /**
     * @param $name
     * @param array $data
     * @return void
     */
    function hook($name, $data = [])
    {
        // Will be implemented
    }
}

if (!function_exists('magicView')) {
    /**
     * @param $extension
     * @return Array
     */
    function magicView($view, $data = [])
    {
        if (
            request()->wantsJson() &&
            !request()->has('partialRequest') &&
            !request()->has('limanJSRequest')
        ) {
            return response()->json($data);
        } else {
            return response()->view($view, $data);
        }
    }
}

if (!function_exists('getExtensionJson')) {
    /**
     * @param $extension
     * @return Array
     */
    function getExtensionJson($extension_name)
    {
        $extension_json = "/liman/extensions/" .
                strtolower($extension_name) .
                DIRECTORY_SEPARATOR .
                "db.json";

        if (file_exists($extension_json)) {
            return json_decode(
                file_get_contents(
                    $extension_json
                ),
                true
            );
        } else {
            abort(404, __("Bu eklenti klasörü bulunmamaktadır."));
        }
    }
}

if (!function_exists('redirect_now')) {
    function redirect_now($url, $code = 302)
    {
        try {
            \App::abort($code, '', ['Location' => $url]);
        } catch (\Exception $exception) {
            $previousErrorHandler = set_exception_handler(function () {});
            restore_error_handler();
            call_user_func($previousErrorHandler, $exception);
            die();
        }
    }
}
if (!function_exists('extensionDb')) {
    /**
     * @param $key
     * @return String
     */
    function extensionDb($key = "*")
    {
        $target = DB::table("user_settings")
            ->where([
                "user_id" => auth()->user()->id,
                "server_id" => server()->id,
                "name" => $key,
            ])
            ->first();
        if ($key == "clientPassword" || $key == "clientUsername") {
            $serverKey = server()->key();
            if ($serverKey == null) {
                return null;
            }
            $data = json_decode($serverKey->data, true);
            $encKey = env('APP_KEY') . auth()->user()->id . server()->id;
            return AES256::decrypt($data[$key], $encKey);
        }
        if ($target) {
            $key = env('APP_KEY') . auth()->user()->id . server()->id;
            return AES256::decrypt($target->value, $key);
        }
        return null;
    }
}

if (!function_exists('sudo')) {
    function sudo()
    {
        if (server()->key()->type == "ssh_certificate") {
            return "sudo ";
        }
        $pass64 = base64_encode(extensionDb("clientPassword") . "\n");
        return 'echo ' .
            $pass64 .
            ' | base64 -d | sudo -S -p " " id 2>/dev/null 1>/dev/null; sudo ';
    }
}

if (!function_exists('getObject')) {
    /**
     * @param $type
     * @param null $id
     * @return Extension|bool|Model|Builder|object
     */
    function getObject($type, $id = null)
    {
        // Check for type
        switch ($type) {
            case "Extension":
            case "extension":
                return Extension::find($id);
                break;
            case "Server":
            case "server":
                return Server::find($id);
                break;
            default:
                return false;
        }
    }
}

if (!function_exists('objectToArray')) {
    /**
     * @param $array
     * @param $key
     * @param $value
     * @return array
     */
    function objectToArray($array, $key, $value)
    {
        $combined_array = [];
        foreach ($array as $item) {
            if (is_array($item)) {
                $combined_array[$item[$key]] = $item[$value];
            } else {
                $combined_array[$item->__get($key)] = $item->__get($value);
            }
        }
        return $combined_array;
    }
}

if (!function_exists('cleanArray')) {
    function cleanArray($array)
    {
        $newArray = [];
        foreach ($array as $row) {
            $newArray[] = $row;
        }
        return $newArray;
    }
}

if (!function_exists('cities')) {
    /**
     * @param null $city
     * @return array|false|int|string
     */
    function cities($city = null)
    {
        $cities = json_decode(
            file_get_contents(storage_path("cities.json")),
            true
        );
        if ($city) {
            return array_search($city, $cities);
        }
        return $cities;
    }
}

if (!function_exists('cleanDash')) {
    /**
     * @return array|Request|string
     */
    function cleanDash($text)
    {
        return str_replace('-', '', $text);
    }
}
if (!function_exists('isJson')) {
    function isJson($string, $return_data = false)
    {
        $data = json_decode($string);
        return json_last_error() == JSON_ERROR_NONE
            ? ($return_data
                ? $data
                : true)
            : false;
    }
}

if (!function_exists('getPermissions')) {
    function getPermissions($path)
    {
        return substr(sprintf("%o", fileperms($path)), -4);
    }
}
if (!function_exists('setEnv')) {
    function setEnv(array $values)
    {
        $editor = DotenvEditor::load(base_path('.env'));
        $editor->setKeys($values);
        $editor->save();
        shell_exec('php /liman/server/artisan config:clear');
        return true;
    }
}
if (!function_exists('checkHealth')) {
    function checkHealth()
    {
        $allowed = [
            "certs" => "0700",
            "database" => "0700",
            "extensions" => "0755",
            "keys" => "0755",
            "logs" => "0700",
            "sandbox" => "0755",
            "server" => "0700",
            "webssh" => "0700",
            "modules" => "0700",
            "packages" => "0700",
            "hashes" => "0700",
        ];
        $messages = [];

        // Check Permissions and Owners
        foreach ($allowed as $name => $permission) {
            // Permission Check
            $file = "/liman/" . $name;
            if (!file_exists($file)) {
                array_push($messages, [
                    "type" => "danger",
                    "message" => "'/liman/$name' isimli sistem dosyası bulunamadı",
                ]);
                continue;
            }

            if (getPermissions('/liman/' . $name) != $permission) {
                array_push($messages, [
                    "type" => "danger",
                    "message" =>
                        "'/liman/$name' izni hatalı (" .
                        getPermissions('/liman/' . $name) .
                        ").",
                ]);
            }

            // Owners Check
            $owner = posix_getpwuid(fileowner($file))["name"];
            $group = posix_getgrgid(filegroup($file))["name"];
            if ($owner != "liman" || $group != "liman") {
                array_push($messages, [
                    "type" => "danger",
                    "message" => "'/liman/$name' dosyasının sahibi hatalı ($owner : $group).",
                ]);
            }
        }

        // Check Extra Files
        $extra = array_diff(
            array_diff(scandir("/liman"), ['..', '.']),
            array_keys($allowed)
        );
        foreach ($extra as $item) {
            array_push($messages, [
                "type" => "warning",
                "message" => "'/liman/$item' dosyasina izin verilmiyor.",
            ]);
        }
        if (empty($messages)) {
            array_push($messages, [
                "type" => "success",
                "message" => __("Herşey Yolunda, sıkıntı yok!"),
            ]);
        }

        return $messages;
    }
}

if (!function_exists('lDecrypt')) {
    function lDecrypt($data)
    {
        $key = env('APP_KEY') . user()->id . server()->id;
        return AES256::decrypt($data, $key);
    }
}

if (!function_exists('setBaseDn')) {
    function setBaseDn($ldap_host = null)
    {
        $ldap_host = $ldap_host ? $ldap_host : config('ldap.ldap_host');
        $flag = false;
        $connection = ldap_connect($ldap_host, 389);
        ldap_set_option($connection, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($connection, LDAP_OPT_NETWORK_TIMEOUT, 10);
        ldap_set_option($connection, LDAP_OPT_TIMELIMIT, 10);
        $flag = ldap_bind($connection);
        $outputs = ldap_read($connection, '', 'objectclass=*');
        $entries = ldap_get_entries($connection, $outputs)[0];
        $domain = str_replace(
            "dc=",
            "",
            strtolower($entries["rootdomainnamingcontext"][0])
        );
        $domain = str_replace(",", ".", $domain);
        setEnv([
            "LDAP_BASE_DN" => $entries["rootdomainnamingcontext"][0],
            "LDAP_DOMAIN" => $domain,
        ]);
        return $flag;
    }
}

if (!function_exists('checkPort')) {
    function checkPort($ip, $port)
    {
        if ($port == -1) {
            return true;
        }
        $fp = @fsockopen($ip, $port, $errno, $errstr, 0.1);
        if (!$fp) {
            return false;
        } else {
            fclose($fp);
            return true;
        }
    }
}
if (!function_exists('endsWith')) {
    function endsWith($string, $endString)
    {
        $len = strlen($endString);
        if ($len == 0) {
            return true;
        }
        return substr($string, -$len) === $endString;
    }
}

if (!function_exists('fetchExtensionTemplates')) {
    function fetchExtensionTemplates()
    {
        $path = storage_path('extension_templates/templates.json');
        return json_decode(file_get_contents($path));
    }
}

if (!function_exists('scanTranslations')) {
    function scanTranslations($directory)
    {
        $pattern =
            '[^\w]' .
            '(?<!->)' .
            '(' .
            implode('|', ['__']) .
            ')' .
            "\(" .
            "[\'\"]" .
            '(' .
            '.+' .
            ')' .
            "[\'\"]" .
            "[\),]";
        $allMatches = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory)
        );
        foreach ($iterator as $file) {
            if ($file->isDir()) {
                continue;
            }
            if (endsWith($file->getPathname(), ".php")) {
                $content = file_get_contents($file->getPathname());
                if (preg_match_all("/$pattern/siU", $content, $matches)) {
                    foreach ($matches[2] as $row) {
                        $allMatches[$row] = $row;
                    }
                }
            }
        }
        return $allMatches;
    }
}
