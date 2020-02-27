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
use App\JobHistory;

class ExtensionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public $extension,$server,$user,$function,$parameters,$request,$session,$cookie,$history;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($history,$server,$extension,$user,$function,$parameters)
    {
        $this->history = $history;
        $this->extension = $extension;
        $this->server = $server;
        $this->user = $user;
        $this->function = $function;
        $this->parameters = $parameters;
        $this->session = session()->all();
        $this->cookie = isset($_COOKIE["liman_session"]) ? $_COOKIE["liman_session"] : '';
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $request = [];
        $parameters = json_decode($this->parameters);
        foreach($parameters as $key=>$param){
            $request[$key] = $param;
        }
        $this->request = $request;
        $command = self::sandbox($this->server, $this->extension, $this->extension->id,$this->user->id, "null", "null", $this->function);
        $output = shell_exec($command);
        system_log(7,"EXTENSION_BACKGROUND_RUN",[
            "extension_id" => $this->extension->id,
            "server_id" => $this->server->id,
            "target_name" => $this->function
        ]);

        $code = 200;
        try{
            $json = json_decode($output,true);
            if(array_key_exists("status",$json)){
                $code = intval($json["status"]);
            }
        }catch (\Exception $exception){};
        if(strval($code) == "200" && $json["message"] != ""){
            $this->history->status = 1;
            $this->history->save();
            return true;
        }else{
            $this->history->status = 2;
            $this->history->save();
            return false;
        }
    }

    private function sandbox($serverObj, $extensionObj, $extension_id, $user_id, $outputs, $viewName, $functionName,$extensionDb = null)
    {
        $functions = env('EXTENSIONS_PATH') . strtolower($extensionObj["name"]) . "/views/functions.php";

        $combinerFile = env('SANDBOX_PATH') . "index.php";

        $server = json_encode($serverObj->toArray());

        $extension = json_encode($extensionObj);
        
        if($extensionDb == null){
            $settings = DB::table("user_settings")->where([
                "user_id" => $user_id,
                "server_id" => $serverObj->id,
            ]);
            $extensionDb = [];
            foreach ($settings->get() as $setting){
                $key = env('APP_KEY') . $user_id . $extension_id . $serverObj->id;
                $decrypted = openssl_decrypt($setting->value,'aes-256-cfb8',$key);
                $stringToDecode = substr($decrypted,16);
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

        $apiRoute = route('extension_function_api', [
            "extension_id" => $extension_id
        ]);

        $navigationRoute = route('extension_server_route', [
            "server_id" => $serverObj->id,
            "extension_id" => $extension_id,
            "city" => $serverObj->city
        ]);

        $token = Token::create($user_id);

        if(!$this->user->isAdmin()){
            $extensionJson = json_decode(file_get_contents(env("EXTENSIONS_PATH") .strtolower($extensionObj->name) . DIRECTORY_SEPARATOR . "db.json"),true);
            $permissions = [];
            if(array_key_exists("functions",$extensionJson)){
                foreach($extensionJson["functions"] as $item){
                    if(Permission::can($user_id,"function","name",strtolower($extensionObj->name),$item["name"]) || $item["isActive"] != "true"){
                        array_push($permissions,$item["name"]);
                    };
                }
            }
            $permissions = json_encode($permissions);
        }else{
            $permissions = "admin";
        }
        $sessionData = json_encode($this->session);
        $array = [$functions,strtolower($extensionObj->name),
            $viewName,$server,$extension,$extensionDb,$outputsJson,$request,$functionName,
            $apiRoute,$navigationRoute,$token,$extension_id,$permissions, 'tr',$this->cookie,$sessionData];
        $encrypted = openssl_encrypt(Str::random() . base64_encode(json_encode($array)),
            'aes-256-cfb8',shell_exec('cat ' . env('KEYS_PATH') . DIRECTORY_SEPARATOR . $extension_id),
            0,Str::random());
        $keyPath = env('KEYS_PATH') . DIRECTORY_SEPARATOR . $extension_id;
        
        $command = "sudo runuser " . clean_score($extension_id) .
            " -c 'timeout 30 /usr/bin/php -d display_errors=on $combinerFile $keyPath $encrypted'";
        return $command;
    }

}
