<?php

namespace App\Http\Controllers\Permission;

use App\Http\Controllers\Controller;
use App\Models\LimanRequest;
use App\Models\Notification;
use App\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * Permission Controller
 *
 * @extends Controller
 */
class MainController extends Controller
{

    /**
     * Get all requests list
     *
     * @return JsonResponse|Response
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
     * Get request
     *
     * Add permission_id to request body to retrieve data as JSON
     *
     * @return JsonResponse|Response
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

        return magicView('permission.requests.' . $request->type, [
            'request' => $request,
        ]);
    }

    /**
     * Update request
     *
     * Send request_id
     * Send status (1:In Progress, 2:Completed, 3:Deny, 4:Delete)
     *
     * @return JsonResponse|Response
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
