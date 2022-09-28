<?php

namespace App\Http\Controllers\Permission;

use App\Http\Controllers\Controller;
use App\Models\LimanRequest;
use App\Models\Notification;
use App\Models\Permission;
use App\User;

class MainController extends Controller
{
    /**
     * @api {get} /talepler Get System Requests
     * @apiName Get System Requests
     * @apiGroup Notification
     *
     * @apiSuccess {JSON} message Message with randomly notification token.
     */
    public function all()
    {
        $requests = LimanRequest::all();
        foreach ($requests as $request) {
            $user = User::find($request->user_id);
            if (! $user) {
                $request->user_name = 'Kullanici Silinmis';
                $request->user_id = '';
            } else {
                $request->user_name = $user->name;
                $request->user_id = $user->id;
            }
            $request->type = match ($request->type) {
                'server' => __('Sunucu'),
                'extension' => __('Eklenti'),
                'other' => __('Diğer'),
                default => __('Bilinmeyen.'),
            };
            $request->status = match ($request->status) {
                '0' => __('Talep Alındı'),
                '1' => __('İşleniyor'),
                '2' => __('Tamamlandı.'),
                '3' => __('Reddedildi.'),
                default => __('Bilinmeyen.'),
            };
            switch ($request->speed) {
                case 'normal':
                    $request->speed = __('Normal');
                    break;
                case 'urgent':
                    $request->speed = __('ACİL');
                    break;
            }
        }
        system_log(7, 'REQUEST_LIST');

        return magicView('permission.list', [
            'requests' => $requests,
        ]);
    }

    /**
     * @api {get} /talep/{permission_id} Get Request
     * @apiName Get Request
     * @apiGroup Notification
     *
     * @apiParam {String} permission_id ID of the permission
     *
     * @apiSuccess {JSON} message Message with randomly notification token.
     */
    public function one()
    {
        $request = LimanRequest::where('id', request('permission_id'))->first();
        $request->user_name = User::where(
            'id',
            $request->user_id
        )->first()->name;

        system_log(7, 'REQUEST_DETAILS', [
            'request_id' => $request,
        ]);

        return magicView('permission.requests.'.$request->type, [
            'request' => $request,
        ]);
    }

    /**
     * @api {post} /talep/guncelle Update System Request
     * @apiName Update System Request
     * @apiGroup Notification
     *
     * @apiParam {String} request_id ID of the request
     * @apiParam {String} status 1:In Progress, 2:Completed, 3:Deny, 4:Delete
     *
     * @apiSuccess {JSON} message Message with randomly notification token.
     */
    public function requestUpdate()
    {
        $request = LimanRequest::where('id', request('request_id'))->first();

        system_log(7, 'REQUEST_UPDATE', [
            'action' => $request,
        ]);

        $text = match (request('status')) {
            '0' => __('Talep Alındı'),
            '1' => __('İşleniyor'),
            '2' => __('Tamamlandı.'),
            '3' => __('Reddedildi.'),
            '4' => __('Silindi.'),
            default => __('Bilinmeyen.'),
        };
        Notification::send(
            __('Talebiniz güncellendi'),
            'notify',
            __('Talebiniz ":status" olarak güncellendi.', [
                'status' => $text,
            ]),
            $request->user_id
        );
        if (request('status') == '4') {
            $request->delete();

            return respond('Talep Silindi', 200);
        }

        $request->update([
            'status' => request('status'),
        ]);

        return respond('Talep Güncellendi', 200);
    }
}
