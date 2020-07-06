<?php

namespace App\Http\Controllers\Notification;

use Illuminate\Http\Request;
use App\Models\ExternalNotification;
use Illuminate\Support\Str;

class ExternalNotificationController extends Controller
{
    public function create()
    {
        $token = (string) Str::uuid();
        if (
            ExternalNotification::create(
                request()
                    ->merge(["token" => $token])
                    ->all()
            )
        ) {
            return respond("Token Oluşturuldu!\n$token");
        } else {
            return respond("Token Oluşturulamadı!", 201);
        }
    }

    public function revoke()
    {
        $obj = ExternalNotification::find(request("id"));
        if (!$obj) {
            return respond("Bu istemci bulunamadı!", 201);
        }

        if ($obj->delete()) {
            return respond("Başarıyla Silindi!");
        } else {
            return respond("Silinemedi!", 201);
        }
    }

    public function renew()
    {
        $obj = ExternalNotification::find(request("id"));
        if (!$obj) {
            return respond("Bu istemci bulunamadı!", 201);
        }
        $token = (string) Str::uuid();
        if (
            $obj->update([
                "token" => $token,
            ])
        ) {
            return respond("Token başarıyla yenilendi!\n$token");
        }
    }

    public function edit()
    {
        $obj = ExternalNotification::find(request("id"));
        if (!$obj) {
            return respond("Bu istemci bulunamadı!", 201);
        }
        if ($obj->update(request()->all())) {
            return respond("İstemci Güncellendi!");
        } else {
            return respond("İstemci Güncellenemedi!", 201);
        }
    }

    public function accept()
    {
    }
}
