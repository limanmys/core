<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use App\Token;
use Illuminate\Support\Str;
use App\Permission;

class ExtensionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $extension,
        $server,
        $user,
        $function,
        $parameters,
        $request,
        $session,
        $cookie,
        $sandbox,
        $history;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        $history,
        $server,
        $extension,
        $user,
        $function,
        $parameters
    ) {
        $this->history = $history;
        $this->extension = $extension;
        $this->server = $server;
        $this->user = $user;
        $this->function = $function;
        $this->parameters = $parameters;
        $this->session = session()->all();
        foreach ($parameters as $key => $param) {
            request()->request->add([$key => $param]);
        }
        $this->sandbox = sandbox();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $command = $this->sandbox->command($this->function);
        
        $output = shell_exec($command);
        
        system_log(7, "EXTENSION_BACKGROUND_RUN", [
            "extension_id" => $this->extension->id,
            "server_id" => $this->server->id,
            "target_name" => $this->function,
        ]);

        $code = 200;
        try {
            $json = json_decode($output, true);
            if (array_key_exists("status", $json)) {
                $code = intval($json["status"]);
            }
        } catch (\Exception $exception) {
        }
        if (strval($code) == "200" && $json["message"] != "") {
            $this->history->status = 1;
            $this->history->save();
            return true;
        } else {
            $this->history->status = 2;
            $this->history->save();
            return false;
        }
    }

    private function sandbox(
        $serverObj,
        $extensionObj,
        $extension_id,
        $user_id,
        $outputs,
        $viewName,
        $functionName,
        $extensionDb = null
    ) {
        $functions =
            "/liman/extensions/" .
            strtolower($extensionObj["name"]) .
            "/views/functions.php";

        $combinerFile = "/liman/sandbox/php/index.php";

        $server = json_encode($serverObj->toArray());

        $extension = json_encode($extensionObj);

        if ($extensionDb == null) {
            $settings = DB::table("user_settings")->where([
                "user_id" => $user_id,
                "server_id" => $serverObj->id,
            ]);
            $extensionDb = [];
            foreach ($settings->get() as $setting) {
                $key =
                    env('APP_KEY') . $user_id . $extension_id . $serverObj->id;
                $decrypted = openssl_decrypt(
                    $setting->value,
                    'aes-256-cfb8',
                    $key
                );
                $stringToDecode = substr($decrypted, 16);
                $extensionDb[$setting->name] = base64_decode($stringToDecode);
            }
        }

        $extensionDb = json_encode($extensionDb);

        $outputsJson = json_encode($outputs);

        $request = $this->request;
        unset($request["permissions"]);
        unset($request["extension"]);
        unset($request["server"]);
        unset($request["script"]);
        unset($request["server_id"]);
        $request = json_encode($request);

        $apiRoute = route('extension_server', [
            "extension_id" => $extension_id,
        ]);

        $navigationRoute = route('extension_server_route', [
            "server_id" => $serverObj->id,
            "extension_id" => $extension_id,
            "city" => $serverObj->city,
        ]);

        $token = Token::create($user_id);

        if (!$this->user->isAdmin()) {
            $extensionJson = json_decode(
                file_get_contents(
                    "/liman/extensions/" .
                        strtolower($extensionObj->name) .
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
                            $user_id,
                            "function",
                            "name",
                            strtolower($extensionObj->name),
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
        $sessionData = json_encode($this->session);
        $array = [
            $functions,
            strtolower($extensionObj->name),
            $viewName,
            $server,
            $extension,
            $extensionDb,
            $outputsJson,
            $request,
            $functionName,
            $apiRoute,
            $navigationRoute,
            $token,
            $extension_id,
            $permissions,
            'tr',
            $this->cookie,
            $sessionData,
        ];
        $encrypted = openssl_encrypt(
            Str::random() . base64_encode(json_encode($array)),
            'aes-256-cfb8',
            shell_exec(
                'cat ' . '/liman/keys' . DIRECTORY_SEPARATOR . $extension_id
            ),
            0,
            Str::random()
        );
        $keyPath = '/liman/keys' . DIRECTORY_SEPARATOR . $extension_id;

        $command =
            "sudo runuser " .
            cleanDash($extension_id) .
            " -c 'timeout 30 /usr/bin/php -d display_errors=on $combinerFile $keyPath $encrypted'";
        return $command;
    }
}
