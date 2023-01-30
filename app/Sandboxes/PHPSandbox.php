<?php

namespace App\Sandboxes;

use App\Models\Permission;
use App\Models\Token;
use App\Models\UserSettings;
use Illuminate\Support\Str;
use mervick\aesEverywhere\AES256;

/**
 * DEPRECATED
 * WILL BE REMOVED IN NEXT VERSIONS
 */
class PHPSandbox implements Sandbox
{
    private $path = '/liman/sandbox/php/index.php';

    private $fileExtension = '.blade.php';

    private $server;

    private $extension;

    private $user;

    private $request;

    private $logId;

    /**
     * @param $server
     * @param $extension
     * @param $user
     * @param $request
     */
    public function __construct(
        $server = null,
        $extension = null,
        $user = null,
        $request = null
    )
    {
        $this->server = $server ? $server : server();
        $this->extension = $extension ? $extension : extension();
        $this->user = $user ? $user : user();
        $this->request = $request
            ? $request
            : request()->except([
                'permissions',
                'extension',
                'server',
                'script',
                'server_id',
            ]);
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param $logId
     * @return void
     */
    public function setLogId($logId)
    {
        $this->logId = $logId;
    }

    /**
     * @return string
     */
    public function getFileExtension()
    {
        return $this->fileExtension;
    }

    /**
     * @param $function
     * @param $extensionDb
     * @return string
     * @throws \Exception
     */
    public function command($function, $extensionDb = null)
    {
        $combinerFile = $this->path;

        $settings = UserSettings::where([
            'user_id' => $this->user->id,
            'server_id' => $this->server->id,
        ]);
        if ($extensionDb == null) {
            $extensionDb = [];
            foreach ($settings->get() as $setting) {
                $key =
                    env('APP_KEY') .
                    $this->user->id .
                    $this->server->id;
                $extensionDb[$setting->name] = AES256::decrypt($setting->value, $key);
            }

            $extensionDb = json_encode($extensionDb);
        }

        $request = json_encode($this->request);

        $apiRoute = route('extension_server', [
            'extension_id' => $this->extension->id,
            'server_id' => $this->server->id,
        ]);

        $navigationRoute = route('extension_server', [
            'server_id' => $this->server->id,
            'extension_id' => $this->extension->id,
        ]);

        $token = Token::create($this->user->id);

        if (! $this->user->isAdmin()) {
            $extensionJson = json_decode(
                file_get_contents(
                    '/liman/extensions/' .
                    strtolower((string) $this->extension->name) .
                    DIRECTORY_SEPARATOR .
                    'db.json'
                ),
                true
            );
            $permissions = [];
            if (array_key_exists('functions', $extensionJson)) {
                foreach ($extensionJson['functions'] as $item) {
                    if (
                        Permission::can(
                            $this->user->id,
                            'function',
                            'name',
                            strtolower((string) $this->extension->name),
                            $item['name']
                        ) ||
                        $item['isActive'] != 'true'
                    ) {
                        array_push($permissions, $item['name']);
                    }
                }
            }
            $permissions = json_encode($permissions);
        } else {
            $permissions = 'admin';
        }

        $userData = [
            'id' => $this->user->id,
            'name' => $this->user->name,
            'email' => $this->user->email,
        ];

        $functionsPath =
            '/liman/extensions/' .
            strtolower((string) $this->extension->name) .
            '/views/functions.php';

        $publicPath = route('extension_public_folder', [
            'extension_id' => $this->extension->id,
            'path' => '',
        ]);

        $isAjax = request()->wantsJson() ? true : false;
        $array = [
            $functionsPath,
            $function,
            $this->server->toArray(),
            $this->extension->toArray(),
            $extensionDb,
            $request,
            $apiRoute,
            $navigationRoute,
            $token,
            $permissions,
            // session('locale'),
            // json_encode($userData),
            $publicPath,
            // $isAjax,
            $this->logId,
        ];

        $encrypted = openssl_encrypt(
            Str::random() . base64_encode(json_encode($array)),
            'aes-256-cfb8',
            shell_exec(
                'cat ' .
                '/liman/keys' .
                DIRECTORY_SEPARATOR .
                $this->extension->id
            ),
            0,
            Str::random()
        );

        $keyPath = '/liman/keys' . DIRECTORY_SEPARATOR . $this->extension->id;

        $soPath =
            '/liman/extensions/' .
            strtolower((string) $this->extension->name) .
            '/liman.so';

        $extra = is_file($soPath) ? "-dextension=$soPath " : '';

        return 'sudo runuser ' .
            cleanDash($this->extension->id) .
            " -c 'timeout 30 /usr/bin/php $extra-d display_errors=on $combinerFile $keyPath $encrypted'";
    }

    /**
     * @return string[]
     */
    public function getInitialFiles()
    {
        return ['index.blade.php', 'functions.php'];
    }
}
