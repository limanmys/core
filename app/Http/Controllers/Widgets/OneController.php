<?php

namespace App\Http\Controllers\Widgets;

use App\Extension;
use App\Server;
use App\Token;
use App\Widget;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class OneController extends Controller
{
    public function one()
    {
        $widget = Widget::find(\request('widget_id'));
        if(!$widget){
            return respond("Widget Bulunamadı",201);
        }
        $extension =  Extension::one($widget->extension_id);
        $server = Server::find($widget->server_id);
        request()->request->add(['server' => $server]);
        request()->request->add(['widget' => $widget]);
        request()->request->add(['extension_id' => $extension->_id]);
        request()->request->add(['extension' => $extension]);
        $command = self::generateSandboxCommand($server, $extension, auth()->user()->settings, auth()->id(), "null", "null", $widget->widget_name);
        return __("<b>Geçici Olarak Devre Dışı</b>");
    }

    public function remove()
    {
        $widget = Widget::find(\request('widget_id'));
        $widget->delete();
        return respond("Başarıyla silindi");
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
        return respond("Başarıyla güncellendi.");
    }

    public function extensions()
    {
        $extensions = [];
        foreach (extensions() as $extension) {
            if($extension->widgets && array_key_exists($extension->_id,server()->extensions)){
                $extensions[$extension->_id] = $extension->name;
            }
        }
        return $extensions;
    }

    public function widgetList()
    {
        return extension()->widgets;
    }

    private function generateSandboxCommand($serverObj, \App\Extension $extensionObj, $user_settings, $user_id, $outputs, $viewName, $functionName)
    {
        $functions = env('EXTENSIONS_PATH') . strtolower($extensionObj->name) . "/functions.php";

        $combinerFile = env('SANDBOX_PATH') . "index.php";

        $server = str_replace('"', '*m*', json_encode($serverObj->toArray()));

        $extension = str_replace('"', '*m*', json_encode($extensionObj->toArray()));

        if (
            !array_key_exists($serverObj->_id, $user_settings) ||
            !array_key_exists($extensionObj->_id, $user_settings[$serverObj->_id])
        ) {
            $extensionDb = "";
        } else {
            $extensionDb = str_replace('"', '*m*', json_encode($user_settings[$serverObj->_id][$extensionObj->_id]));
        }

        $outputsJson = str_replace('"', '*m*', json_encode($outputs));

        $request = request()->all();
        unset($request["permissions"]);
        unset($request["extension"]);
        unset($request["server"]);
        unset($request["script"]);
        unset($request["server_id"]);
        $request = str_replace('"', '*m*', json_encode($request));

        $apiRoute = route('extension_function_api', [
            "extension_id" => $extensionObj->_id,
            "function_name" => ""
        ]);

        $navigationRoute = route('extension_server_route', [
            "server_id" => $serverObj->_id,
            "extension_id" => $extensionObj->_id,
            "city" => $serverObj->city,
            "unique_code" => ""
        ]);

        $token = Token::create($user_id);

        $command = "sudo runuser liman-" . $extensionObj->_id .
            " -c '/usr/bin/php -d display_errors=on $combinerFile $functions "
            . strtolower($extensionObj->name) .
            " $viewName \"$server\" \"$extension\" \"$extensionDb\" \"$outputsJson\" \"$request\" \"$functionName\" \"$apiRoute\" \"$navigationRoute\" \"$token\"'";

        return $command;
    }

}
