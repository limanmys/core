<?php

namespace App\Http\Controllers\Extension;

use App\Http\Controllers\Controller;
use App\Models\UserSettings;
use Carbon\Carbon;
use function request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use mervick\aesEverywhere\AES256;
use GuzzleHttp\Client;
use App\Models\Token;

/**
 * Class OneController
 * @package App\Http\Controllers\Extension
 */
class OneController extends Controller
{
    /**
     * @return RedirectResponse|Redirector
     */
    public function serverSettings()
    {
        $extension = json_decode(
            file_get_contents(
                "/liman/extensions/" .
                    strtolower(extension()->name) .
                    DIRECTORY_SEPARATOR .
                    "db.json"
            ),
            true
        );
        foreach ($extension["database"] as $key) {
            if (
                $key["type"] == "password" &&
                request($key["variable"]) !=
                    request($key["variable"] . '_confirmation')
            ) {
                return redirect(
                    route('extension_server_settings_page', [
                        "extension_id" => extension()->id,
                        "server_id" => server()->id,
                        "city" => server()->city,
                    ])
                )
                    ->withInput()
                    ->withErrors([
                        "message" => __("Parola alanları uyuşmuyor!"),
                    ]);
            }
        }

        foreach ($extension["database"] as $key) {
            $row = DB::table('user_settings')->where([
                "user_id" => user()->id,
                "server_id" => server()->id,
                'name' => $key["variable"],
            ]);
            $variable = request($key["variable"]);
            if ($variable) {
                if ($row->exists()) {
                    $encKey = env('APP_KEY') . user()->id . server()->id;
                    $row->update([
                        "value" => AES256::encrypt($variable, $encKey),
                        "updated_at" => Carbon::now(),
                    ]);
                } else {
                    $encKey = env('APP_KEY') . user()->id . server()->id;
                    DB::table("user_settings")->insert([
                        "id" => Str::uuid(),
                        "server_id" => server()->id,
                        "user_id" => user()->id,
                        "name" => $key["variable"],
                        "value" => AES256::encrypt($variable, $encKey),
                        "created_at" => Carbon::now(),
                        "updated_at" => Carbon::now(),
                    ]);
                }
            }
        }

        //Check Verification
        if (
            array_key_exists("verification", $extension) &&
            $extension["verification"] != null &&
            $extension["verification"] != ""
        ) {
            $client = new Client(['verify' => false]);
            $result = "";
            try {
                $res = $client->request('POST', env("RENDER_ENGINE_ADDRESS","https://127.0.0.1:5454"), [
                    'form_params' => [
                        "lmntargetFunction" => $extension["verification"],
                        "extension_id" => extension()->id,
                        "server_id" => server()->id,
                        "token" => Token::create(user()->id),
                    ],
                    'timeout' => 5,
                ]);
                $output = (string) $res->getBody();
                if (isJson($output)) {
                    $message = json_decode($output);
                    if (isset($message->message)) {
                        $result = $message->message;
                    }
                } else {
                    $result = $output;
                }
            } catch (\Exception $e) {
                $result = $e->getMessage();
            }
            if (trim($result) != "ok") {
                return redirect(
                    route('extension_server_settings_page', [
                        "extension_id" => extension()->id,
                        "server_id" => server()->id,
                        "city" => server()->city,
                    ])
                )
                    ->withInput()
                    ->withErrors([
                        "message" => $result,
                    ]);
            }
        }
        system_log(7, "EXTENSION_SETTINGS_UPDATE", [
            "extension_id" => extension()->id,
            "server_id" => server()->id,
        ]);

        return redirect(
            route('extension_server', [
                "extension_id" => extension()->id,
                "server_id" => server()->id,
                "city" => server()->city,
            ])
        );
    }

    /**
     * @return Response
     */
    public function serverSettingsPage()
    {
        $extension = json_decode(
            file_get_contents(
                "/liman/extensions/" .
                    strtolower(extension()->name) .
                    DIRECTORY_SEPARATOR .
                    "db.json"
            ),
            true
        );
        system_log(7, "EXTENSION_SETTINGS_PAGE", [
            "extension_id" => extension()->id,
        ]);
        $similar = [];
        $flag = server()->key();
        foreach ($extension["database"] as $key => $item) {
            if (
                ($flag != null && $item["variable"] == "clientUsername") ||
                ($flag != null && $item["variable"] == "clientPassword")
            ) {
                unset($extension["database"][$key]);
            }
            $obj = DB::table("user_settings")
                ->where([
                    "user_id" => user()->id,
                    "name" => $item["variable"],
                    "server_id" => server()->id,
                ])
                ->first();
            if ($obj) {
                $key = env('APP_KEY') . user()->id . server()->id;
                $similar[$item["variable"]] = AES256::decrypt(
                    $obj->value,
                    $key
                );
            }
        }

        if (env('LIMAN_RESTRICTED') == true && !user()->isAdmin()) {
            return magicView('extension_pages.setup_restricted', [
                'extension' => $extension,
                'similar' => $similar,
                'extensionDb' => extensionDb(),
            ]);
        }

        return magicView('extension_pages.setup', [
            'extension' => $extension,
            'similar' => $similar,
            'extensionDb' => extensionDb(),
        ]);
    }

    /**
     * @return JsonResponse|Response
     */
    public function remove()
    {
        $ext_name = extension()->name;
        hook('extension_delete_attempt', extension());
        try {
            shell_exec(
                "rm -rf " . "/liman/extensions/" . strtolower(extension()->name)
            );
        } catch (\Exception $exception) {
        }

        try {
            rootSystem()->userRemove(extension()->id);
            extension()->delete();
        } catch (\Exception $exception) {
        }

        hook('extension_delete_successful', [
            "request" => request()->all(),
        ]);

        if(is_file(storage_path("extension_updates"))){
            $json = json_decode(file_get_contents(storage_path("extension_updates")),true);
            for($i = 0; $i < count($json); $i++){
                if($json[$i]["name"] == $ext_name){
                    unset($json[$i]);
                }
            }
            file_put_contents(storage_path("extension_updates"),json_encode($json));
        }
        
        system_log(3, "EXTENSION_REMOVE");
        return respond('Eklenti Başarıyla Silindi');
    }

    public function publicFolder()
    {
        $basePath =
            "/liman/extensions/" . strtolower(extension()->name) . "/public/";

        $targetPath = $basePath . base64_decode(request('path'));

        if (realpath($targetPath) != $targetPath) {
            abort(404);
        }

        if (is_file($targetPath)) {
            return response()->download($targetPath);
        } else {
            abort(404);
        }
    }
}
