<?php

namespace App\Http\Controllers\Widgets;

use App\Extension;
use App\Server;
use App\Token;
use App\Widget;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class OneController extends Controller
{
    public function one()
    {
        $widget = Widget::find(\request('widget_id'));
        if(!$widget){
            return respond(__("Widget Bulunamadı"),201);
        }
        $extension =  Extension::one($widget->extension_id);
        $extensionData = json_decode(file_get_contents(env("EXTENSIONS_PATH") .strtolower(extension($widget->extension_id)->name) . DIRECTORY_SEPARATOR . "db.json"),true);
        foreach ($extensionData["database"] as $item){
            if(!DB::table("user_settings")->where([
                "user_id" => auth()->user()->id,
                "server_id" => $widget->server_id,
                "extension_id" => $extension->id,
                "name" => $item["variable"]
            ])->exists()){
                return respond(__("Eklenti ayarları eksik.") . " <a href='".url('ayarlar/'.$extension->id.'/'.$widget->server_id)."'>".__("Ayarlara Git.")."</a>", 400);
            }
        }
        $server = Server::find($widget->server_id);
        request()->request->add(['server' => $server]);
        request()->request->add(['widget' => $widget]);
        request()->request->add(['extension_id' => $extension->id]);
        request()->request->add(['extension' => $extension]);
        $command = self::generateSandboxCommand($server, $extension, "", auth()->id(), "null", "null", $widget->function);
        $output = shell_exec($command);
        if(!$output){
            return respond(__("Widget Hiçbir Veri Döndürmedi"), 400);
        }
        $output_json = json_decode($output, true);
        if(!isset($output_json)){
          return respond(__("Boş json nesnesi."), 400);
        }
        return respond(isset($output_json['message']) ? $output_json['message'] : $output_json,
          isset($output_json['status']) ? $output_json['status'] : 200);
    }

    public function remove()
    {
        $widget = Widget::find(\request('widget_id'));
        $widget->delete();
        return respond(__("Başarıyla silindi"));
    }

    public function update()
    {
        $widget = Widget::find(\request('widget_id'));
        $widget->update([
            "server_id" => \request('server_id'),
            "extension_id" => \request('extension_id'),
            "title" => \request('title'),
            "function_name" => \request('function_name')
        ]);
        return respond(__("Başarıyla güncellendi."));
    }

    public function extensions()
    {
        $extensions = [];
        foreach (server()->extensions() as $extension){
            $extensions[$extension->id] = $extension->name;
        }
        return $extensions;
    }

    public function widgetList()
    {
        $extension = json_decode(file_get_contents(env("EXTENSIONS_PATH") .strtolower(extension()->name) . DIRECTORY_SEPARATOR . "db.json"),true);
        return $extension["widgets"];
    }

    private function generateSandboxCommand($serverObj, $extensionObj, $extension_id, $user_id, $outputs, $viewName, $functionName)
    {
        if(!$extension_id){
            $extension_id = extension()->id;
        }
        $functions = env('EXTENSIONS_PATH') . strtolower($extensionObj["name"]) . "/functions.php";

        $combinerFile = env('SANDBOX_PATH') . "index.php";

        $server = str_replace('"', '*m*', json_encode($serverObj->toArray()));

        $extension = str_replace('"', '*m*', json_encode($extensionObj));

        $settings = DB::table("user_settings")->where([
            "user_id" => $user_id,
            "server_id" => server()->id,
            "extension_id" => extension()->id
        ]);
        $extensionDb = [];
        foreach ($settings->get() as $setting){
            $extensionDb[$setting->name] = $setting->value;
        }

        $extensionDb = str_replace('"', '*m*', json_encode($extensionDb));

        $outputsJson = str_replace('"', '*m*', json_encode($outputs));

        $request = request()->all();
        unset($request["permissions"]);
        unset($request["extension"]);
        unset($request["server"]);
        unset($request["script"]);
        unset($request["server_id"]);
        $request = str_replace('"', '*m*', json_encode($request));

        $apiRoute = route('extension_function_api', [
            "extension_id" => extension()->id,
            "function_name" => ""
        ]);

        $navigationRoute = route('extension_server_route', [
            "server_id" => $serverObj->id,
            "extension_id" => extension()->id,
            "city" => $serverObj->city,
            "unique_code" => ""
        ]);

        $token = Token::create($user_id);

        $command = "sudo runuser " . clean_score(extension()->id) .
            " -c 'timeout 5 /usr/bin/php -d display_errors=on $combinerFile $functions "
            . strtolower(extension()->name) .
            " $viewName \"$server\" \"$extension\" \"$extensionDb\" \"$outputsJson\" \"$request\" \"$functionName\" \"$apiRoute\" \"$navigationRoute\" \"$token\" \"$extension_id\"'";

        return $command;
    }

}
