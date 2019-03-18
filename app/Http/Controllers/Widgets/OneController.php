<?php

namespace App\Http\Controllers\Widgets;

use App\Extension;
use App\Server;
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
        if(server()->serverless){
            return self::script();
        }else{
            return self::serverless();
        }
    }

    public function serverless()
    {
        require_once(base_path('resources/views/extensions/' . strtolower(request('extension')->name) . '/functions.php'));
        return call_user_func(request('widget')->widget_name);
    }

    public function script()
    {
        $script = \App\Script::where('unique_code',request('widget')->widget_name);
        if(!$script){
            return respond("Betik Bulunamadı",201);
        }
        $parameters = '';
        foreach (explode(',', $script->inputs) as $input) {
            $parameters = $parameters . " " . \request(explode(':', $input)[0]);
        }
        $output = request('server')->runScript($script, $parameters);
        $output = str_replace('\n', '', $output);
        return $output;
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

}
