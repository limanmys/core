<?php

namespace App\Http\Controllers\Extension;

use App\Http\Controllers\Controller;
use App\UserSettings;
use Carbon\Carbon;
use function request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
        $extension = json_decode(file_get_contents(env("EXTENSIONS_PATH") . strtolower(extension()->name) . DIRECTORY_SEPARATOR . "db.json"), true);
        foreach ($extension["database"] as $key) {
            if ($key["type"] == "password" && request($key["variable"]) != request($key["variable"] . '_confirmation')) {
                return redirect(route('extension_server_settings_page', [
                    "extension_id" => extension()->id,
                    "server_id" => server()->id,
                    "city" => server()->city
                ]))->withInput()->withErrors([
                    "message" => __("Parola alanları uyuşmuyor!")
                ]);
            }
        }
        //Check Verification
        if (array_key_exists("verification", $extension) && $extension["verification"] != null && $extension["verification"] != "") {
            // Run Function
            $extensionDb = [];
            foreach ($extension["database"] as $key) {
                if (request($key["variable"])) {
                    $extensionDb[$key["variable"]] = request($key["variable"]);
                } elseif ($setting = UserSettings::where([
                    "user_id" => user()->id,
                    "server_id" => server()->id,
                    'name' => $key["variable"]
                ])->first()) {
                    $extensionDb[$key["variable"]] = lDecrypt($setting->value);
                } else {
                    return redirect(route('extension_server_settings_page', [
                        "extension_id" => extension()->id,
                        "server_id" => server()->id,
                        "city" => server()->city
                    ]))->withInput()->withErrors([
                        "message" => "Eksik parametre girildi."
                    ]);
                }
            }
            $extensionDb = json_encode($extensionDb);
            $command = sandbox()->command($extension["verification"], $extensionDb);
            $output = shell_exec($command);
            if (isJson($output)) {
                $message = json_decode($output);
                if (isset($message->message)) {
                    $output = $message->message;
                }
            }

            $sessions = \App\TmpSession::where('session_id', session()->getId())->get();
            foreach ($sessions as $session) {
                session()->put($session->key, $session->value);
                $session->delete();
            }

            if (strtolower($output) != "ok" && strtolower($output) != "ok\n") {
                return redirect(route('extension_server_settings_page', [
                    "extension_id" => extension()->id,
                    "server_id" => server()->id,
                    "city" => server()->city
                ]))->withInput()->withErrors([
                    "message" => $output
                ]);
            }
        }
        foreach ($extension["database"] as $key) {
            $row = DB::table('user_settings')->where([
                "user_id" => user()->id,
                "server_id" => server()->id,
                'name' => $key["variable"]
            ]);
            if (request($key["variable"])) {
                if ($row->exists()) {
                    $encKey = env('APP_KEY') . user()->id . extension()->id . server()->id;
                    $encrypted = openssl_encrypt(Str::random(16) . base64_encode(request($key["variable"])), 'aes-256-cfb8', $encKey, 0, Str::random(16));
                    $row->update([
                        "value" => $encrypted,
                        "updated_at" => Carbon::now(),
                    ]);
                } else {
                    $encKey = env('APP_KEY') . user()->id . extension()->id . server()->id;
                    $encrypted = openssl_encrypt(Str::random(16) . base64_encode(request($key["variable"])), 'aes-256-cfb8', $encKey, 0, Str::random(16));

                    DB::table("user_settings")->insert([
                        "id" => Str::uuid(),
                        "server_id" => server()->id,
                        "user_id" => user()->id,
                        "name" => $key["variable"],
                        "value" => $encrypted,
                        "created_at" =>  Carbon::now(),
                        "updated_at" => Carbon::now(),
                    ]);
                }
            }
        }
        system_log(7, "EXTENSION_SETTINGS_UPDATE", [
            "extension_id" => extension()->id,
            "server_id" => server()->id,
        ]);

        return redirect(route('extension_server', [
            "extension_id" => extension()->id,
            "server_id" => server()->id,
            "city" => server()->city
        ]));
    }

    /**
     * @return Response
     */
    public function serverSettingsPage()
    {
        $extension = json_decode(file_get_contents(env("EXTENSIONS_PATH") . strtolower(extension()->name) . DIRECTORY_SEPARATOR . "db.json"), true);
        system_log(7, "EXTENSION_SETTINGS_PAGE", [
            "extension_id" => extension()->id
        ]);
        $similar = [];
        foreach ($extension["database"] as $item) {
            if (strpos(strtolower($item["variable"]), "password")) {
                continue;
            }
            $obj = DB::table("user_settings")->where([
                "user_id" => user()->id,
                "name" => $item["variable"],
                "server_id" => server()->id
            ])->first();
            if ($obj) {
                $key = env('APP_KEY') . user()->id . extension()->id . server()->id;
                $decrypted = openssl_decrypt($obj->value, 'aes-256-cfb8', $key);
                $stringToDecode = substr($decrypted, 16);
                $similar[$item["variable"]] = base64_decode($stringToDecode);
            }
        }

        return response()->view('extension_pages.setup', [
            'extension' => $extension,
            'similar' => $similar
        ]);
    }

    /**
     * @return JsonResponse|Response
     */
    public function remove()
    {
        hook('extension_delete_attempt', extension());
        try {
            shell_exec("sudo rm -r " . env('EXTENSIONS_PATH') . strtolower(extension()->name));
        } catch (Exception $exception) {
        }

        try {
            shell_exec('sudo userdel ' . cleanDash(extension()->id));
            shell_exec('rm ' . env('KEYS_PATH') . DIRECTORY_SEPARATOR . extension()->id);
            extension()->delete();
        } catch (Exception $exception) {
        }

        hook('extension_delete_successful', [
            "request" => request()->all()
        ]);

        system_log(3, "EXTENSION_REMOVE");
        return respond('Eklenti Başarıyla Silindi');
    }

    public function publicFolder()
    {
        $basePath = env('EXTENSIONS_PATH') . strtolower(extension()->name) . "/public/";

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
