<?php

use App\Exceptions\JsonResponseException;
use App\Models\Certificate;
use App\Models\Extension;
use App\Models\Permission;
use App\Models\Server;
use App\Models\SystemSettings;
use App\System\Helper;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use mervick\aesEverywhere\AES256;
use sixlive\DotenvEditor\DotenvEditor;

if (! function_exists('validate')) {
    /**
     * Validator class wrapper
     * It validates user request datas
     *
     * @param $rules
     * @param array $messages
     * @param array $fieldNames
     * @return void
     * @throws JsonResponseException
     */
    function validate($rules, array $messages = [], array $fieldNames = []): void
    {
        $validator = Validator::make(request()->all(), $rules, $messages);
        $validator->setAttributeNames($fieldNames);
        if (! request()->wantsJson()) {
            // If request doesn't want JSON handle as the old way
            if ($validator->fails()) {
                $errors = $validator->errors();
                abort(400, $errors->first());
            }
        } else {
            // If request wants JSON handle with new way
            if ($validator->fails()) {
                $errors = $validator->errors();
                $customFormat = [];
                foreach ($errors->toArray() as $key => $value) {
                    $customFormat[$key] = $value[0];
                }

                throw new JsonResponseException($customFormat, null, Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        }
    }
}

if (! function_exists('updateSystemSettings')) {
    /**
     * Update system settings
     * This function used by high availability syncer
     *
     * @return void
     */
    function updateSystemSettings()
    {
        SystemSettings::updateOrCreate(
            ['key' => 'APP_KEY'],
            ['data' => env('APP_KEY')]
        );

        SystemSettings::updateOrCreate(
            ['key' => 'SSL_PUBLIC_KEY'],
            ['data' => file_get_contents('/liman/certs/liman.crt')]
        );

        SystemSettings::updateOrCreate(
            ['key' => 'SSL_PRIVATE_KEY'],
            ['data' => file_get_contents('/liman/certs/liman.key')]
        );
        $sshPublic = SystemSettings::where([
            'key' => 'SSH_PUBLIC',
        ])->first();
        if (! $sshPublic) {
            $privatekey = \phpseclib3\Crypt\RSA::createKey();
            $publickey = $privatekey->getPublicKey();
            `mkdir -p /home/liman/.ssh`;
            file_put_contents('/home/liman/.ssh/authorized_keys', $publickey);
            file_put_contents('/home/liman/.ssh/liman_pub', $publickey);
            file_put_contents('/home/liman/.ssh/liman_priv', $privatekey);

            chmod('/home/liman/.ssh/liman_pub', 0600);
            chmod('/home/liman/.ssh/liman_priv', 0600);

            SystemSettings::create([
                'key' => 'SSH_PUBLIC',
                'data' => $publickey,
            ]);

            SystemSettings::updateOrCreate(
                ['key' => 'SSH_PRIVATE_KEY'],
                ['data' => $privatekey]
            );
        }
    }
}

if (! function_exists('receiveSystemSettings')) {
    /**
     * Receive system settings
     * This function used by high availability syncer
     *
     * @return void
     */
    function receiveSystemSettings()
    {
        $app_key = SystemSettings::where([
            'key' => 'APP_KEY',
        ])->first();

        if ($app_key) {
            setEnv([
                'APP_KEY' => $app_key->data,
            ]);
        }

        $public_key = SystemSettings::where([
            'key' => 'SSL_PUBLIC_KEY',
        ])->first();

        if ($public_key) {
            file_put_contents('/liman/certs/liman.crt', $public_key->data);
        }

        $private_key = SystemSettings::where([
            'key' => 'SSL_PRIVATE_KEY',
        ])->first();

        if ($private_key) {
            file_put_contents('/liman/certs/liman.key', $private_key->data);
        }

        $sshPublic = SystemSettings::where([
            'key' => 'SSH_PUBLIC',
        ])->first();

        if ($sshPublic) {
            `mkdir -p /home/liman/.ssh`;
            file_put_contents(
                '/home/liman/.ssh/authorized_keys',
                $sshPublic->data
            );
            file_put_contents('/home/liman/.ssh/liman_pub', $sshPublic->data);
            chmod('/home/liman/.ssh/liman_pub', 0600);
        }

        $sshPrivate = SystemSettings::where([
            'key' => 'SSH_PRIVATE_KEY',
        ])->first();

        if ($sshPrivate) {
            `mkdir -p /home/liman/.ssh`;
            file_put_contents('/home/liman/.ssh/liman_priv', $sshPrivate->data);
            chmod('/home/liman/.ssh/liman_priv', 0600);
        }
    }
}

if (! function_exists('respond')) {
    /**
     * Returns a response object within the scope of request
     *
     * @param $message
     * @param int $status
     * @return \Illuminate\Http\JsonResponse|Response
     */
    function respond($message, $status = 200)
    {
        return response()->json(
            [
                'message' => is_array($message) ? $message : __($message),
                'status' => $status,
            ],
            $status
        );
    }
}

if (! function_exists('ip_in_range')) {
    /**
     * Calculates is ip in range
     *
     * @param $ip
     * @param $range
     * @return bool
     */
    function ip_in_range($ip, $range)
    {
        if (strpos((string) $range, '/') == false) {
            $range .= '/32';
        }
        // $range is in IP/CIDR format eg 127.0.0.1/24
        [$range, $netmask] = explode('/', (string) $range, 2);
        $range_decimal = ip2long($range);
        $ip_decimal = ip2long($ip);
        $wildcard_decimal = pow(2, 32 - $netmask) - 1;
        $netmask_decimal = ~$wildcard_decimal;

        return ($ip_decimal & $netmask_decimal) ==
            ($range_decimal & $netmask_decimal);
    }
}

if (! function_exists('rootSystem')) {
    /**
     * Get new system helper instance
     *
     * @return Helper
     */
    function rootSystem()
    {
        return new Helper();
    }
}

if (! function_exists('getLimanPermissions')) {
    /**
     * Get liman permission list
     *
     * @return mixed
     */
    function getLimanPermissions($user_id)
    {
        $map = [
            'view_logs' => 'Sunucu Günlük Kayıtlarını Görüntüleme',
            'add_server' => 'Sunucu Ekleme',
            'server_services' => 'Sunucu Servislerini Görüntüleme',
            'server_details' => 'Sunucu Detaylarını Görüntüleme',
            'update_server' => 'Sunucu Detaylarını Güncelleme',
        ];
        $permissions = Permission::where([
            'morph_id' => $user_id ? $user_id : user()->id,
            'type' => 'liman',
            'key' => 'id',
        ])->get();
        $permissions = $permissions->map(function ($permission) use (&$map) {
            $permission->name = __($map[$permission->value]);
            $permission->id = $permission->value;

            return $permission;
        });

        return $permissions;
    }
}

if (! function_exists('getVersion')) {
    /**
     * Get version of liman
     *
     * @return mixed
     */
    function getVersion(): mixed
    {
        return file_get_contents(storage_path('VERSION'));
    }
}

if (! function_exists('getVersionCode')) {
    /**
     * Get version code of liman
     *
     * @return mixed
     */
    function getVersionCode(): int
    {
        return intval(file_get_contents(storage_path('VERSION_CODE')));
    }
}

if (! function_exists('knownPorts')) {
    /**
     * Get known ports for certificate checking
     *
     * @return string[]
     */
    function knownPorts()
    {
        $ports = ['5986', '443'];
        if (! env('LDAP_IGNORE_CERT', false)) {
            array_push($ports, '636');
        }
        return $ports;
    }
}

if (! function_exists('retrieveCertificate')) {
    /**
     * Retrieve certificate content from remote end
     * @param  $hostname
     * @param  $port
     * @return array
     */
    function retrieveCertificate($hostname, $port)
    {
        $get = stream_context_create([
            'ssl' => [
                'capture_peer_cert' => true,
                'allow_self_signed' => true,
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ]);
        $flag = false;

        // Check if hostname is an IP address or not
        if (filter_var($hostname, FILTER_VALIDATE_IP)) {
            $hostname = gethostbyaddr($hostname);
            if (! $hostname) {
                return [false, __('Sertifika alınamıyor!')];
            }
        }

        try {
            $read = stream_socket_client(
                'ssl://' . $hostname . ':' . $port,
                $errno,
                $errstr,
                intval(config('liman.server_connection_timeout')),
                STREAM_CLIENT_CONNECT,
                $get
            );
            $flag = true;
        } catch (\Exception) {
        }

        if (! $flag) {
            try {
                $read = stream_socket_client(
                    'tlsv1.1://' . $hostname . ':' . $port,
                    $errno,
                    $errstr,
                    intval(config('liman.server_connection_timeout')),
                    STREAM_CLIENT_CONNECT,
                    $get
                );
                $flag = true;
            } catch (\Exception) {
                return [false, __('Sertifika alınamıyor!')];
            }
        }

        $cert = stream_context_get_params($read);
        $certinfo = openssl_x509_parse(
            $cert['options']['ssl']['peer_certificate']
        );
        openssl_x509_export(
            $cert['options']['ssl']['peer_certificate'],
            $publicKey
        );
        $certinfo['subjectKeyIdentifier'] = array_key_exists(
            'subjectKeyIdentifier',
            $certinfo['extensions']
        )
            ? $certinfo['extensions']['subjectKeyIdentifier']
            : '';
        $certinfo['authorityKeyIdentifier'] = array_key_exists(
            'authorityKeyIdentifier',
            $certinfo['extensions']
        )
            ? substr((string) $certinfo['extensions']['authorityKeyIdentifier'], 6)
            : '';
        $certinfo['validFrom_time_t'] = Carbon::createFromTimestamp(
            $certinfo['validFrom_time_t']
        )->format('H:i d/m/Y');
        $certinfo['validTo_time_t'] = Carbon::createFromTimestamp(
            $certinfo['validTo_time_t']
        )->format('H:i d/m/Y');
        unset($certinfo['extensions']);
        $path = Str::random(10);
        $certinfo['path'] = $path;
        file_put_contents('/tmp/' . $path, $publicKey);

        return [true, $certinfo];
    }
}

if (! function_exists('addCertificate')) {
    /**
     * Add certificate to system
     *
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    function addCertificate($hostname, $port, $path)
    {
        rootSystem()->addCertificate(
            '/tmp/' . $path,
            'liman-' . $hostname . '_' . $port
        );

        // Create Certificate Object.
        return Certificate::create([
            'server_hostname' => strtolower((string) $hostname),
            'origin' => $port,
        ]);
    }
}

if (! function_exists('system_log')) {
    /**
     * Create a system log
     *
     * @param $level
     * @param $message
     * @param array $array
     */
    function system_log($level, $message, $array = [])
    {
        $array['user_id'] = user() ? user()->id : '';
        $array['ip_address'] = request()->ip();

        match ($level) {
            1 => Log::emergency($message, $array),
            2 => Log::alert($message, $array),
            3 => Log::critical($message, $array),
            4 => Log::error($message, $array),
            5 => Log::warning($message, $array),
            6 => Log::notice($message, $array),
            7 => Log::info($message, $array),
            default => Log::debug($message, $array),
        };
    }
}

if (! function_exists('server')) {
    /**
     * Get current server
     *
     * @return \App\Models\Server
     */
    function server()
    {
        if (! request()->request->get('server')) {
            abort(501, 'Sunucu Bulunamadı');
        }
        $serverObj = json_decode((string) request()->request->get('server'));
        $server = Server::find($serverObj->id);

        return $server;
    }
}

if (! function_exists('extension')) {
    /**
     * Get current extension
     *
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

if (! function_exists('user')) {
    /**
     * Get current logged in user
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    function user()
    {
        return auth()->user();
    }
}

if (! function_exists('getExtensionJson')) {
    /**
     * Get extension json
     *
     * @param $extension_name
     * @return array
     */
    function getExtensionJson($extension_name)
    {
        $extension_json = '/liman/extensions/' .
            strtolower((string) $extension_name) .
            DIRECTORY_SEPARATOR .
            'db.json';

        if (file_exists($extension_json)) {
            $json = json_decode(
                file_get_contents(
                    $extension_json
                ),
                true
            );
            if (empty($json['display_name'])) {
                $json['display_name'] = Str::title(str_replace('-', ' ', (string) $json['name']));
            }

            return $json;
        } else {
            abort(404, $extension_name . __(' eklentisi sistemde bulunamadı, yeniden yüklemeyi deneyin.'));
        }
    }
}

if (! function_exists('extensionDb')) {
    /**
     * Get a variable from extension database
     *
     * @param $key
     * @return string
     * @throws Exception
     */
    function extensionDb($key = '*')
    {
        $target = DB::table('user_settings')
            ->where([
                'user_id' => auth()->user()->id,
                'server_id' => server()->id,
                'name' => $key,
            ])
            ->first();
        if ($key == 'clientPassword' || $key == 'clientUsername') {
            $serverKey = server()->key();
            if ($serverKey == null) {
                return null;
            }
            $data = json_decode((string) $serverKey->data, true);
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

if (! function_exists('sudo')) {
    /**
     * Get sudo query
     *
     * @return string
     */
    function sudo()
    {
        if (server()->key()->type == 'ssh_certificate') {
            return 'sudo ';
        }

        return 'sudo -p "liman-pass-sudo" ';
    }
}

if (! function_exists('getObject')) {
    /**
     * Get server or extension object
     *
     * @param $type
     * @param $id
     */
    function getObject($type, $id = null)
    {
        // Check for type
        switch ($type) {
            case 'Extension':
            case 'extension':
                try {
                    return Extension::find($id);
                } catch (\Throwable) {
                    abort(404, __('Eklenti bulunamadı.'));
                }
                break;
            case 'Server':
            case 'server':
                try {
                    return Server::find($id);
                } catch (\Throwable) {
                    abort(404, __('Sunucu bulunamadı.'));
                }
                break;
            default:
                return false;
        }
    }
}

if (! function_exists('isJson')) {
    /**
     * Check if data is JSON
     *
     * @param $string
     * @param $return_data
     * @return bool|mixed
     */
    function isJson($string, $return_data = false)
    {
        $data = json_decode((string) $string);

        return json_last_error() == JSON_ERROR_NONE
            ? ($return_data
                ? $data
                : true)
            : false;
    }
}

if (! function_exists('getPermissions')) {
    /**
     * Get permissions of file
     *
     * @param $path
     * @return string
     */
    function getPermissions($path): string
    {
        return substr(sprintf('%o', fileperms($path)), -4);
    }
}

if (! function_exists('getExtensionFunctions')) {
    /**
     * Get extension's functions
     *
     * @param string $extension_name
     * @return array|\Illuminate\Support\Collection
     */
    function getExtensionFunctions(string $extension_name)
    {
        $file = '/liman/extensions/' .
            strtolower($extension_name) .
            DIRECTORY_SEPARATOR .
            'db.json';

        if (is_file($file)) {
            $extension = json_decode(
                file_get_contents(
                    $file
                ),
                true
            );
        } else {
            $extension = [];
        }

        return isset($extension['functions'])
            ? collect($extension['functions'])
            : [];
    }
}

if (! function_exists('extensionTranslate')) {
    /**
     * Translate extension
     *
     * @param string $text
     * @param string $extension_name
     * @return mixed|string
     */
    function extensionTranslate(string $text, string $extension_name)
    {
        $lang = session('locale', 'tr');
        $file =
            '/liman/extensions/' .
            strtolower($extension_name) .
            '/lang/' .
            $lang .
            '.json';
        if (is_file($file)) {
            $lang = json_decode(file_get_contents($file), true);

            return isset($lang[$text]) ? $lang[$text] : $text;
        }

        return $text;
    }
}

if (! function_exists('endsWith')) {
    /**
     * Check if string ends with other string
     *
     * @param $string
     * @param $endString
     * @return bool
     */
    function endsWith($string, $endString)
    {
        $len = strlen((string) $endString);
        if ($len == 0) {
            return true;
        }

        return substr((string) $string, -$len) === $endString;
    }
}

if (! function_exists('setEnv')) {
    /**
     * Set environment file
     *
     * @param array $values
     * @return bool
     */
    function setEnv(array $values): bool
    {
        $editor = new DotenvEditor;

        $editor = $editor->load(base_path('.env'));
        foreach ($values as $key => $value) {
            $editor->set($key, $value);
        }
        try {
            $editor->save();
        } catch (\Exception) {
            return false;
        }
        shell_exec('php /liman/server/artisan config:clear');

        return true;
    }
}

if (! function_exists('setBaseDn')) {
    /**
     * Set base DN in .env file
     *
     * @param $ldap_host
     * @return bool
     */
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
            'dc=',
            '',
            strtolower((string) $entries['rootdomainnamingcontext'][0])
        );
        $domain = str_replace(',', '.', $domain);
        setEnv([
            'LDAP_BASE_DN' => $entries['rootdomainnamingcontext'][0],
            'LDAP_DOMAIN' => $domain,
        ]);

        return $flag;
    }
}

if (! function_exists('checkPort')) {
    /**
     * Check if port is online
     *
     * @param $ip
     * @param $port
     * @return bool
     */
    function checkPort($ip, $port)
    {
        if ($port == -1) {
            return true;
        }
        $fp = @fsockopen($ip, $port, $errno, $errstr, 0.1);
        if (! $fp) {
            return false;
        } else {
            fclose($fp);

            return true;
        }
    }
}

if (! function_exists('callExtensionFunction')) {
    function callExtensionFunction(
        Extension $extension,
        Server $server,
        $params = [],
        $target_function = "apiProxy"
    ) {
        if ($extension->require_key == true && $server->key() == null) {
            return null;
        }

        $client = new Client(['verify' => false]);
        try {
            $res = $client->request('POST', env('RENDER_ENGINE_ADDRESS', 'https://127.0.0.1:2806'), [
                'form_params' => [
                    'lmntargetFunction' => $target_function,
                    'extension_id' => $extension->id,
                    'server_id' => $server->id,
                    ...$params,
                ],
                'timeout' => 10,
                'cookies' => convertToCookieJar(request(), '127.0.0.1'),
            ]);
            $output = $res->getBody()->__toString();

            $isJson = isJson($output, true);
            if ($isJson && isset($isJson->status) && $isJson->status == 200) {
                return $isJson->message;
            } else {
                return $output;
            }
        } catch (\Exception $e) {
            return null;
        }

        return null;
    }
}

if (! function_exists('convertToCookieJar')) {
    function convertToCookieJar($request, $host = null) {
        // Add all cookies from original request
        if ($host == null) {
            $host = $request->host();
        }
        return CookieJar::fromArray($request->cookies->all(), $host);
    }
}
