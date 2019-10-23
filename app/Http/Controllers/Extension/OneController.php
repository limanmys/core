<?php

namespace App\Http\Controllers\Extension;

use App\Extension;
use App\Permission;
use App\Server;
use App\Http\Controllers\Controller;
use App\User;
use App\UserSettings;
use Carbon\Carbon;
use function request;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Support\Str;

/**
 * Class OneController
 * @package App\Http\Controllers\Extension
 */
class OneController extends Controller
{
    /**
     * @return Illuminate\View\View
     */
    public function renderView()
    {
        $extension = json_decode(file_get_contents(env("EXTENSIONS_PATH") .strtolower(extension()->name) . DIRECTORY_SEPARATOR . "db.json"),true);
        // Now that we have server, let's check if required parameters set for extension.

        foreach ($extension["database"] as $setting) {
            $database = DB::table("user_settings");
            if (!$database->where([
                "user_id" => user()->id,
                "server_id" => server()->id,
                "name" => $setting["variable"]
            ])->exists()) {
                system_log(7,"EXTENSION_MISSING_SETTINGS",[
                    "extension_id" => extension()->id
                ]);
                return redirect(route('extension_server_settings_page', [
                    "server_id" => server()->id,
                    "extension_id" => extension()->id
                ]));
            }
        }

        $outputs = [];

        $viewName = (request('unique_code')) ? request('unique_code') : "index";
        $flag = false;
        foreach ($extension["views"] as $view){
            if($view["name"] == $viewName){
                $flag = true;
                break;
            }
        }

        if(!$flag){
            system_log(7,"EXTENSION_MISSING_PAGE",[
                "extension_id" => extension()->id
            ]);
            abort(504,"Sayfa bulunamadı");
        }

        if (!is_file(env('EXTENSIONS_PATH') . strtolower(extension()->name) . "/views/" . $viewName . ".blade.php")) {
            system_log(5,"EXTENSION_MISSING_PAGE",[
                "extension_id" => extension()->id,
                "page_name" => $viewName
            ]);
            abort(504,"Sayfa bulunamadı");
        }

        $command = generateSandboxCommand(server(), $extension, "", auth()->id(), $outputs, $viewName, null);
        $before = Carbon::now();
        $output = shell_exec($command);
        $after = Carbon::now();
        system_log(7,"EXTENSION_RENDER_PAGE",[
            "extension_id" => extension()->id,
            "server_id" => server()->id,
            "view" => $viewName
        ]);
        // Return all required parameters.
        return view('extension_pages.server', [
            "viewName" => $viewName,
            "view" => $output,
            "command" => $command,
            "timestamp" => $before->diffInMilliseconds($after) / 1000
        ]);
    }

    /**
     * @return JsonResponse|Response
     */
    public function runFunction()
    {
        if (!Permission::can(auth()->id(), "function", "name",strtolower(extension()->name) ,request('function_name'))) {
            system_log(7,"EXTENSION_NO_PERMISSION",[
                "extension_id" => extension()->id,
                "target_name" => request('function_name')
            ]);
            $function = request("function_name");
            $extensionJson = json_decode(file_get_contents(env("EXTENSIONS_PATH") .strtolower(extension()->name) . DIRECTORY_SEPARATOR . "db.json"),true);
        
            $functions = [];
    
            if(array_key_exists("functions",$extensionJson)){
                $functions = $extensionJson["functions"];
            }
    
            if(empty($functions)){
                return respond("Eklenti icin bu ozellik yapilandirilmamis.",403);
            }
            $flag = false;
            $isActive = "true";
            for($i = 0 ; $i < count($functions); $i++){
                if(request("function_name") == $functions[$i]["name"]){
                    $flag = true;
                    $isActive = $functions[$i]["isActive"];
                    break;
                }
            }
            if(!$flag){
                return respond("Eklenti icin bu ozellik yapilandirilmamis.",403);
            }
            if($isActive == "true" && !Permission::can(user()->id,"function","name",strtolower(extension()->name) , $function)){
                abort(403, $function . " için yetkiniz yok.");
            }
        }

        $command = generateSandboxCommand(server(), extension(), extension()->id,auth()->id(), "null", "null", request('function_name'));

        system_log(7,"EXTENSION_RUN",[
            "extension_id" => extension()->id,
            "server_id" => server()->id,
            "target_name" => request('function_name')
        ]);
        $output = shell_exec($command);
        $code = 200;
        try{
            $json = json_decode($output,true);
            if(array_key_exists("status",$json)){
                $code = intval($json["status"]);
            }
        }catch (\Exception $exception){};
        if(is_json($output)){
          return response()->json(json_decode($output), $code);
        }
        return response($output, $code);
    }

    public function internalExtensionApi()
    {
        if ($_SERVER['SERVER_ADDR'] != $_SERVER['REMOTE_ADDR']) {
            system_log(5,"EXTENSION_INTERNAL_NO_PERMISSION",[
                "extension_id" => extension()->id,
            ]);
            abort(403, 'Not Allowed');
        }
        $token = Token::where('token', request('token'))->first() or abort(403, "Token gecersiz");

        $server = Server::find(request('server_id')) or abort(404, 'Sunucu Bulunamadi');
        if (!Permission::can($token->user_id, 'server','id', $server->id)) {
            system_log(7,"EXTENSION_NO_PERMISSION_SERVER",[
                "extension_id" => extension()->id,
                "server_id" => request('server_id')
            ]);
            return "Sunucu icin yetkiniz yok.";
        }

        $extension = Extension::find(request('extension_id')) or abort(404, 'Eklenti Bulunamadi');
        if (!Permission::can($token->user_id, 'extension','id', $extension->id)) {
            system_log(7,"EXTENSION_NO_PERMISSION_SERVER",[
                "extension_id" => extension()->id,
                "server_id" => request('server_id')
            ]);
            return "Eklenti icin yetkiniz yok.";
        }

        if (!Permission::can($token->user_id, "function", "name",strtolower(extension()->name) ,request('function_name'))) {
            system_log(7,"EXTENSION_NO_PERMISSION",[
                "extension_id" => extension()->id,
                "target_name" => request('function_name')
            ]);
            return request('target') . " fonksiyonu için yetkiniz yok.";
        }

        $user = User::find($token->user_id);
        $command = generateSandboxCommand($server, $extension, $user->settings, $user->id, "null", "null", request('target'));
        $output = shell_exec($command);

        system_log(7,"EXTENSION_INTERNAL_RUN",[
            "extension_id" => extension()->id,
            "server_id" => server()->id,
            "target_name" => request('function_name')
        ]);

        return $output;
    }
    

    public function internalRunCommandApi()
    {
        if ($_SERVER['SERVER_ADDR'] != $_SERVER['REMOTE_ADDR']) {
            system_log(5,"EXTENSION_INTERNAL_NO_PERMISSION",[
                "extension_id" => extension()->id,
            ]);
            return 'Not Allowed';
        }
        $token = Token::where('token', request('token'))->first() or abort(403, "Token gecersiz");

        $server = Server::find(request('server_id')) or abort(404, 'Sunucu Bulunamadi');
        if (!Permission::can($token->user_id, 'server','id', $server->id)) {
            system_log(7,"EXTENSION_NO_PERMISSION_SERVER",[
                "extension_id" => extension()->id,
                "server_id" => request('server_id')
            ]);
            return "Sunucu icin yetkiniz yok.";
        }

        if ($server->type != "linux_ssh" && $server->type != "windows_powershell") {
            system_log(7,"EXTENSION_INTERNAL_RUN_COMMAND_FAILED",[
                "extension_id" => extension()->id,
                "server_id" => request('server_id')
            ]);
            return "Bu sunucuda komut çalıştıramazsınız.";
        }

        request()->request->add(['server' => $server]);
        $output = $server->run(request('command'));

        system_log(7,"EXTENSION_INTERNAL_RUN_COMMAND",[
            "extension_id" => extension()->id,
            "server_id" => server()->id
        ]);

        return $output;
    }

    public function internalRunScriptApi()
    {
        if ($_SERVER['SERVER_ADDR'] != $_SERVER['REMOTE_ADDR']) {
            system_log(5,"EXTENSION_INTERNAL_NO_PERMISSION",[
                "extension_id" => extension()->id,
            ]);
            return 'Not Allowed';
        }
        $token = Token::where('token', request('token'))->first() or abort(403, "Token gecersiz");

        $server = Server::find(request('server_id')) or abort(404, 'Sunucu Bulunamadi');
        if (!Permission::can($token->user_id, 'server','id', $server->id)) {
            system_log(7,"EXTENSION_NO_PERMISSION_SERVER",[
                "extension_id" => extension()->id,
                "server_id" => request('server_id')
            ]);
            return "Sunucu icin yetkiniz yok.";
        }

        $filePath = env("EXTENSIONS_PATH") . strtolower(extension()->name) .
             "/scripts/" . request("scriptName");
        if( !is_file($filePath)){
            system_log(7,"EXTENSION_INTERNAL_RUN_SCRIPT_FAILED_NOT_FOUND",[
                "extension_id" => extension()->id,
                "server_id" => request('server_id')
            ]);
            return "Betik Bulunamadi";
        }

        if ($server->type != "linux_ssh" && $server->type != "windows_powershell") {
            system_log(7,"EXTENSION_INTERNAL_RUN_COMMAND_FAILED",[
                "extension_id" => extension()->id,
                "server_id" => request('server_id')
            ]);
            return "Bu sunucuda komut çalıştıramazsınız.";
        }

        request()->request->add(['server' => $server]);
        $output = $server->runScript($filePath,request("parameters"),request("runAsRoot"));

        system_log(7,"EXTENSION_INTERNAL_RUN_COMMAND",[
            "extension_id" => extension()->id,
            "server_id" => server()->id
        ]);

        return $output;
    }

    public function internalPutFileApi()
    {
        if ($_SERVER['SERVER_ADDR'] != $_SERVER['REMOTE_ADDR']) {
            system_log(5,"EXTENSION_INTERNAL_NO_PERMISSION",[
                "extension_id" => extension()->id,
            ]);
            return 'Not Allowed';
        }
        $token = Token::where('token', request('token'))->first() or abort(403, "Token gecersiz");

        auth()->loginUsingId($token->user_id);

        $server = Server::find(request('server_id')) or abort(404, 'Sunucu Bulunamadi');

        if ($server->type != "linux_ssh" && $server->type != "windows_powershell") {
            system_log(7,"EXTENSION_INTERNAL_RUN_COMMAND_FAILED",[
                "extension_id" => extension()->id,
                "server_id" => server()->id
            ]);
            return "Bu sunucuda komut çalıştıramazsınız.";
        }

        request()->request->add(['server' => $server]);
        $output = $server->putFile(request('localPath'), request('remotePath'));

        system_log(7,"EXTENSION_INTERNAL_SEND_FILE",[
            "extension_id" => extension()->id,
            "server_id" => server()->id,
            "file_name" => request('remotePath'),
        ]);

        return ($output) ? "ok" : "no";
    }

    public function internalGetFileApi()
    {
        if ($_SERVER['SERVER_ADDR'] != $_SERVER['REMOTE_ADDR']) {
            system_log(5,"EXTENSION_INTERNAL_NO_PERMISSION",[
                "extension_id" => extension()->id,
            ]);
            return 'Not Allowed';
        }
        $token = Token::where('token', request('token'))->first() or abort(403, "Token gecersiz");

        auth()->loginUsingId($token->user_id);

        $server = Server::find(request('server_id')) or abort(404, 'Sunucu Bulunamadi');

        if ($server->type != "linux_ssh" && $server->type != "windows_powershell") {
            system_log(7,"EXTENSION_INTERNAL_RUN_COMMAND_FAILED",[
                "extension_id" => extension()->id,
                "server_id" => server()->id
            ]);
            return "Bu sunucuda komut çalıştıramazsınız.";
        }

        request()->request->add(['server' => $server]);
        $server->getFile(request('remotePath'), request('localPath'));
        // Update Permissions
        shell_exec("sudo chmod 770 " . request('localPath'));
        shell_exec("sudo chown " . clean_score(extension()->id) . ":liman " . request('localPath'));
        system_log(7,"EXTENSION_INTERNAL_RECEIVE_FILE",[
            "extension_id" => extension()->id,
            "server_id" => server()->id,
            "file_name" => request('remotePath'),
        ]);

        return "ok";
    }

    /**
     * @return RedirectResponse|Redirector
     */
    public function serverSettings()
    {
        $extension = json_decode(file_get_contents(env("EXTENSIONS_PATH") .strtolower(extension()->name) . DIRECTORY_SEPARATOR . "db.json"),true);
        foreach ($extension["database"] as $key) {
            if($key["type"] == "password" && request($key["variable"]) != request($key["variable"].'_confirmation') ){
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
        if(array_key_exists("verification",$extension) && $extension["verification"] != null && $extension["verification"] != ""){
                // Run Function
                $extensionDb = [];
                foreach ($extension["database"] as $key){
                    if(request($key["variable"])){
                        $extensionDb[$key["variable"]] = request($key["variable"]);
                    }elseif($setting = UserSettings::where([
                        "user_id" => user()->id,
                        "server_id" => server()->id,
                        'name' => $key["variable"]
                    ])->first()){
                        $extensionDb[$key["variable"]] = lDecrypt($setting->value);
                    }else{
                        return redirect(route('extension_server_settings_page', [
                            "extension_id" => extension()->id,
                            "server_id" => server()->id,
                            "city" => server()->city
                        ]))->withInput()->withErrors([
                            "message" => "Eksik parametre girildi."
                        ]);
                    }
                }
                $command = generateSandboxCommand(server(), $extension, extension()->id, auth()->id(), "", "null", $extension["verification"],$extensionDb);
                $output = shell_exec($command);
            if(strtolower($output) != "ok" && strtolower($output) != "ok\n"){
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
            if(request($key["variable"])){
                if($row->exists()){
                    $encKey = env('APP_KEY') . user()->id . extension()->id . server()->id;
                    $encrypted = openssl_encrypt(Str::random(16) . base64_encode(request($key["variable"])),'aes-256-cfb8',$encKey,0,Str::random(16));
                    $row->update([
                        "value" => $encrypted,
                        "updated_at" => Carbon::now(),
                    ]);
                }else{
                    $encKey = env('APP_KEY') . user()->id . extension()->id . server()->id;
                    $encrypted = openssl_encrypt(Str::random(16) . base64_encode(request($key["variable"])),'aes-256-cfb8',$encKey,0,Str::random(16));

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
        system_log(7,"EXTENSION_SETTINGS_UPDATE",[
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
        $extension = json_decode(file_get_contents(env("EXTENSIONS_PATH") .strtolower(extension()->name) . DIRECTORY_SEPARATOR . "db.json"),true);
        system_log(7,"EXTENSION_SETTINGS_PAGE",[
            "extension_id" => extension()->id
        ]);
        $similar = [];
        foreach ($extension["database"] as $item){
            if(strpos(strtolower($item["variable"]),"password")){
                continue;
            }
            $obj = DB::table("user_settings")->where([
                "user_id" => user()->id,
                "name" => $item["variable"],
                "server_id" => server()->id
            ])->first();
            if($obj){
                $key = env('APP_KEY') . user()->id . extension()->id . server()->id;
                $decrypted = openssl_decrypt($obj->value,'aes-256-cfb8',$key);
                $stringToDecode = substr($decrypted,16);
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
        try {
            shell_exec("sudo rm -r " . env('EXTENSIONS_PATH') . strtolower(extension()->name));
        } catch (Exception $exception) {
        }

        try{
            shell_exec('sudo userdel ' . clean_score(extension()->id));
            shell_exec('rm ' . env('KEYS_PATH') . DIRECTORY_SEPARATOR . extension()->id);
            shell_exec('sudo dpkg --remove liman-' . Str::slug(extension()->name));
            extension()->delete();
        }catch (Exception $exception){
        }

        system_log(3,"EXTENSION_REMOVE");
        return respond('Eklenti Başarıyla Silindi');
    }

    /**
     * @return Factory|View
     */
    public function page()
    {
        if (request('page_name') == "functions") {
            $fileName = request('page_name') . '.php';
        } else {
            $fileName = request('page_name') . '.blade.php';
        }
        $file = file_get_contents(env('EXTENSIONS_PATH') . strtolower(extension()->name) . '/views/' . $fileName);
        system_log(7,"EXTENSION_CODE_EDITOR",[
            "extension_id" => extension()->id,
            "file" => $fileName
        ]);
        return view('l.editor', [
            "file" => $file
        ]);
    }

    /**
     * @return JsonResponse|Response
     */
    public function updateCode()
    {
        $file = env('EXTENSIONS_PATH') . strtolower(extension()->name) . '/views/' . request('page') . '.blade.php';
        file_put_contents($file, json_decode(request('code')));
        system_log(7,"EXTENSION_CODE_UPDATE",[
            "extension_id" => extension()->id,
            "file" => request('page')
        ]);
        return respond("Kaydedildi", 200);
    }
}
