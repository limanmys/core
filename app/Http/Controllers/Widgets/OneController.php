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
        $extension =  Extension::one($widget->extension_id);
        $server = Server::find($widget->server_id);
        request()->request->add(['server' => $server]);
        request()->request->add(['extension_id' => $extension->_id]);
        require_once(base_path('resources/views/extensions/' . strtolower($extension->name) . '/functions.php'));
        return call_user_func($widget->function_name);
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
}
