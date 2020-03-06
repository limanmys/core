<?php

namespace App\Classes\Sandbox;

use App\Token;
use Illuminate\Support\Str;
use App\UserSettings;

class PHPSandbox implements Sandbox
{
    private $path = "/liman/sandbox/php/index.php";
    private $fileExtension = ".blade.php";

    public function getPath()
    {
        return $this->path;
    }

    public function getFileExtension()
    {
        return $this->fileExtension;
    }

    public function command($function)
    {
        $combinerFile = $this->path;

        $settings = UserSettings::where([
            "user_id" => user()->id,
            "server_id" => server()->id,
        ]);

        $extensionDb = [];
        foreach ($settings->get() as $setting) {
            $key = env('APP_KEY') . user()->id . extension()->id . server()->id;
            $decrypted = openssl_decrypt($setting->value, 'aes-256-cfb8', $key);
            $stringToDecode = substr($decrypted, 16);
            $extensionDb[$setting->name] = base64_decode($stringToDecode);
        }

        $extensionDb = json_encode($extensionDb);

        $request = request()->all();
        unset($request["permissions"]);
        unset($request["extension"]);
        unset($request["server"]);
        unset($request["script"]);
        unset($request["server_id"]);
        $request = json_encode($request);

        $apiRoute = route('extension_server', [
            "extension_id" => extension()->id,
            "city" => server()->city,
            "server_id" => server()->id
        ]);

        $navigationRoute = route('extension_server', [
            "server_id" => server()->id,
            "extension_id" => extension()->id,
            "city" => server()->city
        ]);

        $token = Token::create(user()->id);

        if (!user()->isAdmin()) {
            $extensionJson = json_decode(file_get_contents(env("EXTENSIONS_PATH") . strtolower(extension()->name) . DIRECTORY_SEPARATOR . "db.json"), true);
            $permissions = [];
            if (array_key_exists("functions", $extensionJson)) {
                foreach ($extensionJson["functions"] as $item) {
                    if (Permission::can(user()->id, "function", "name", strtolower(extension()->name), $item["name"]) || $item["isActive"] != "true") {
                        array_push($permissions, $item["name"]);
                    };
                }
            }
            $permissions = json_encode($permissions);
        } else {
            $permissions = "admin";
        }

        $userData = [
            "id" => user()->id,
            "name" => user()->name,
            "email" => user()->email
        ];

        $sessionData = json_encode(session()->all());

        $functionsPath = env('EXTENSIONS_PATH') . strtolower(extension()->name) . "/views/functions.php";

        $array = [
            $functionsPath, $function, server()->toArray(), extension()->toArray(), $extensionDb,
            $request, $apiRoute, $navigationRoute, $token, $permissions, session('locale'), json_encode($userData)
        ];
        
        $encrypted = openssl_encrypt(
            Str::random() . base64_encode(json_encode($array)),
            'aes-256-cfb8',
            shell_exec('cat ' . env('KEYS_PATH') . DIRECTORY_SEPARATOR . extension()->id),
            0,
            Str::random()
        );

        $keyPath = env('KEYS_PATH') . DIRECTORY_SEPARATOR . extension()->id;
        $combinerFile = "/liman/sandbox/php/index.php";
        $command = "sudo runuser " . clean_score(extension()->id) .
            " -c 'timeout 30 /usr/bin/php -d display_errors=on $combinerFile $keyPath $encrypted'";
        return $command;
    }

    public function getInitialFiles()
    {
        return [
            "index.blade.php", "functions.php"
        ];
    }
}
