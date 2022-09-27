<?php

namespace App\Http\Controllers\Widgets;

use App\Http\Controllers\Controller;
use App\Models\Server;
use App\Models\Widget;

class MainController extends Controller
{
    public function add()
    {
        if (
            ! auth()
                ->user()
                ->isAdmin() &&
            Widget::where('user_id', auth()->user()->id)->count() >
                intval(config('liman.user_widget_count'))
        ) {
            return respond(
                'Bileşen kotanızı aştınız, yeni widget ekleyemezsiniz'
            );
        }
        if (
            Widget::where([
                'name' => explode(':', request('widget_name'))[0],
                'text' => explode(':', request('widget_name'))[3],
                'title' => explode(':', request('widget_name'))[1],
                'user_id' => auth()->user()->id,
                'extension_id' => extension()->id,
                'server_id' => server()->id,
                'function' => explode(':', request('widget_name'))[0],
                'type' => explode(':', request('widget_name'))[2],
            ])->exists()
        ) {
            return respond(
                'Bu sunucu için aynı widget daha önce zaten eklenmiş',
                201
            );
        }
        $widget = Widget::create([
            'name' => explode(':', request('widget_name'))[0],
            'text' => explode(':', request('widget_name'))[3],
            'title' => explode(':', request('widget_name'))[1],
            'user_id' => auth()->user()->id,
            'extension_id' => extension()->id,
            'server_id' => server()->id,
            'function' => explode(':', request('widget_name'))[0],
            'type' => explode(':', request('widget_name'))[2],
        ]);

        return respond('Bileşen Eklendi', 200);
    }

    public function settings()
    {
        $widgets = Widget::where('user_id', auth()->id())->get();
        foreach ($widgets as $widget) {
            $widget->server_name = Server::where(
                'id',
                $widget->server_id
            )->first()->name;
        }

        return view('widgets.settings', [
            'widgets' => $widgets,
        ]);
    }

    public function update_orders()
    {
        foreach (json_decode(request('widgets')) as $widget) {
            $data = Widget::find($widget->id);
            $data->update([
                'order' => $widget->order,
            ]);
        }

        return respond('Bileşenler güncellendi', 200);
    }
}
