<?php

namespace App\Classes\Sandbox;

use App\Permission;
use App\Token;
use Illuminate\Support\Str;
use App\UserSettings;

class PHPSandbox implements Sandbox
{
    private $path = "/liman/sandbox/php/index.php";
    private $fileExtension = ".blade.php";
    private $server, $extension, $user, $request, $logId;

    public function __construct(
        $server = null,
        $extension = null,
        $user = null,
        $request = null
    ) {
        $this->server = $server ? $server : server();
        $this->extension = $extension ? $extension : extension();
        $this->user = $user ? $user : user();
        $this->request = $request
            ? $request
            : request()->except([
                "permissions",
                "extension",
                "server",
                "script",
                "server_id",
            ]);
    }

    public function getPath()
    {
        return $this->path;
    }

    public function setLogId($logId)
    {
        $this->logId = $logId;
    }

    public function getFileExtension()
    {
        return $this->fileExtension;
    }

    public function command($function, $extensionDb = null)
    {
        $combinerFile = $this->path;

        $settings = UserSettings::where([
            "user_id" => $this->user->id,
            "server_id" => $this->server->id,
        ]);
        if ($extensionDb == null) {
            $extensionDb = [];
            foreach ($settings->get() as $setting) {
                $key =
                    env('APP_KEY') .
                    $this->user->id .
                    $this->extension->id .
                    $this->server->id;
                $decrypted = openssl_decrypt(
                    $setting->value,
                    'aes-256-cfb8',
                    $key
                );
                $stringToDecode = substr($decrypted, 16);
                $extensionDb[$setting->name] = base64_decode($stringToDecode);
            }

            $extensionDb = json_encode($extensionDb);
        }

        $request = json_encode($this->request);

        $apiRoute = route('extension_server', [
            "extension_id" => $this->extension->id,
            "city" => $this->server->city,
            "server_id" => $this->server->id,
        ]);

        $navigationRoute = route('extension_server', [
            "server_id" => $this->server->id,
            "extension_id" => $this->extension->id,
            "city" => $this->server->city,
        ]);

        $token = Token::create($this->user->id);

        if (!$this->user->isAdmin()) {
            $extensionJson = json_decode(
                file_get_contents(
                    "/liman/extensions/" .
                        strtolower($this->extension->name) .
                        DIRECTORY_SEPARATOR .
                        "db.json"
                ),
                true
            );
            $permissions = [];
            if (array_key_exists("functions", $extensionJson)) {
                foreach ($extensionJson["functions"] as $item) {
                    if (
                        Permission::can(
                            $this->user->id,
                            "function",
                            "name",
                            strtolower($this->extension->name),
                            $item["name"]
                        ) ||
                        $item["isActive"] != "true"
                    ) {
                        array_push($permissions, $item["name"]);
                    }
                }
            }
            $permissions = json_encode($permissions);
        } else {
            $permissions = "admin";
        }

        $userData = [
            "id" => $this->user->id,
            "name" => $this->user->name,
            "email" => $this->user->email,
        ];

        $functionsPath =
            "/liman/extensions/" .
            strtolower($this->extension->name) .
            "/views/functions.php";

        $publicPath = route('extension_public_folder', [
            "extension_id" => $this->extension->id,
            "path" => "",
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
            session('locale'),
            json_encode($userData),
            $publicPath,
            $isAjax,
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
            "/liman/extensions/" .
            strtolower($this->extension->name) .
            "/liman.so";

        $extra = is_file($soPath) ? "-dextension=$soPath " : "";
        return "sudo runuser " .
            cleanDash($this->extension->id) .
            " -c 'timeout 30 /usr/bin/php $extra-d display_errors=on $combinerFile $keyPath $encrypted'";
    }

    public function getInitialFiles()
    {
        return ["index.blade.php", "functions.php"];
    }
}
