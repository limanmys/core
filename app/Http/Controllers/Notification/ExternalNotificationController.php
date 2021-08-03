<?php

namespace App\Http\Controllers\Notification;

use Illuminate\Http\Request;
use App\Models\ExternalNotification;
use App\Models\AdminNotification;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use Validator;
use Carbon\Carbon;
class ExternalNotificationController extends Controller
{
    /**
     * @api {post} /ayar/bildirimKanali/ekle Add External Notification Channel
     * @apiName Add External Notification Channel
     * @apiGroup Notification
     *
     * @apiParam {String} name Name of The Token
     *
     * @apiSuccess {JSON} message Message with randomly notification token.
     */
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

    /**
     * @api {post} /ayar/bildirimKanali/sil Remove External Notification Channel
     * @apiName Remove External Notification Channel
     * @apiGroup Notification
     *
     * @apiParam {String} id ID of the notification
     *
     * @apiSuccess {JSON} message
     */
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

    /**
     * @api {post} /ayar/bildirimKanali/yenile Renew External Notification Channel Token
     * @apiName Renew External Notification Channel Token
     * @apiGroup Notification
     *
     * @apiParam {String} id ID of the notification
     *
     * @apiSuccess {JSON} message Message with randomly notification token.
     */
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

    /**
     * @api {post} /ayar/bildirimKanali/duzenle Edit External Notification Channel
     * @apiName Edit External Notification Channel
     * @apiGroup Notification
     *
     * @apiParam {String} id ID of the notification
     * @apiParam {String} name Name of the notification
     *
     * @apiSuccess {JSON} message Message with randomly notification token.
     */
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

    public function accept(Request $request)
    {
        $channel = ExternalNotification::where('token', request("token"))->first();
        if(!$channel){
            return response()->json([
                "Not authorized, token missing"
            ],403);
        }
        if (ip_in_range($request->ip(),$channel->ip) == false) {
            return response()->json([
                "Not authorized, unacceptable ip block"
            ],403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|max:120', 
            'message' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                "Not Acceptable, inputs invalid"
            ],406);
        }

        AdminNotification::create([
            "title" => __("Dış Bildirim -> ") . $request->get('title'),
            "type" => "external_notification",
            "message" => $request->get('message'),
            "level" => 3,
        ]);

        $channel->update([
            "last_used" => Carbon::now()
        ]);

        return response()->json([
            "OK"
        ]);
    }
}
