<?php

namespace App\Http\Controllers\Widgets;

use App\Http\Controllers\Controller;
use App\Models\Widget;

class OneController extends Controller
{
    public function remove()
    {
        $widget = Widget::find(\request('widget_id'));
        $widget->delete();

        return respond(__('Başarıyla silindi'));
    }

    public function update()
    {
        $widget = Widget::find(\request('widget_id'));
        $widget->update([
            'server_id' => \request('server_id'),
            'extension_id' => \request('extension_id'),
            'title' => \request('title'),
            'function_name' => \request('function_name'),
        ]);

        return respond(__('Başarıyla güncellendi.'));
    }

    public function extensions()
    {
        $extensions = [];
        foreach (server()->extensions() as $extension) {
            $extensions[$extension->id] = $extension->display_name;
        }

        return $extensions;
    }

    public function widgetList()
    {
        $extension = json_decode(
            file_get_contents(
                '/liman/extensions/'.
                    strtolower((string) extension()->name).
                    DIRECTORY_SEPARATOR.
                    'db.json'
            ),
            true
        );

        return $extension['widgets'];
    }
}
