<?php

namespace App\Http\Controllers\Widgets;

use App\Extension;
use App\Permission;
use App\Server;
use App\Token;
use App\Widget;
use Illuminate\Http\Request;
use App\UserSettings;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Http\Controllers\Extension\Sandbox\MainController;

class OneController extends Controller
{
    public function one()
    {
        $widget = Widget::find(\request('widget_id'));
        if (!$widget) {
            return respond(__("Bileşen Bulunamadı"), 201);
        }
        $extension = Extension::one($widget->extension_id);
        $extensionData = json_decode(
            file_get_contents(
                "/liman/extensions/" .
                    strtolower(extension($widget->extension_id)->name) .
                    DIRECTORY_SEPARATOR .
                    "db.json"
            ),
            true
        );
        foreach ($extensionData["database"] as $item) {
            if (
                !UserSettings::where([
                    "user_id" => auth()->user()->id,
                    "server_id" => $widget->server_id,
                    "name" => $item["variable"],
                ])->exists()
            ) {
                return respond(
                    __("Eklenti ayarları eksik.") .
                        " <a href='" .
                        url(
                            'ayarlar/' .
                                $extension->id .
                                '/' .
                                $widget->server_id
                        ) .
                        "'>" .
                        __("Ayarlara Git.") .
                        "</a>",
                    400
                );
            }
        }

        $server = Server::find($widget->server_id);
        request()->request->add([
            'server' => $server,
            'widget' => $widget,
            'extension_id' => $extension->id,
            'extension' => $extension,
            'target_function' => $widget->function,
        ]);

        $sandboxController = new MainController();
        $output = $sandboxController->API()->content();

        if (!$output) {
            return respond(__("Bileşen Hiçbir Veri Döndürmedi"), 400);
        }
        $output_json = json_decode($output, true);
        if (!isset($output_json)) {
            return respond(__("Bilinmeyen bir hata oluştu."), 400);
        }
        return respond($output_json['message'], $output_json['status']);
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
            "function_name" => \request('function_name'),
        ]);
        return respond(__("Başarıyla güncellendi."));
    }

    public function extensions()
    {
        $extensions = [];
        foreach (server()->extensions() as $extension) {
            $extensions[$extension->id] = $extension->name;
        }
        return $extensions;
    }

    public function widgetList()
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
        return $extension["widgets"];
    }
}
