<?php

namespace App\Http\Controllers\Notification;

use App\Http\Controllers\Controller;
use App\Models\ExternalNotification;
use App\Models\Notification;
use App\Models\Role;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Validator;

/**
 * External Notification Controller
 * This controller manages user end of external notifications
 *
 * @extends Controller
 */
class ExternalNotificationController extends Controller
{
    public function revoke()
    {
        $obj = ExternalNotification::find(request('id'));
        if (!$obj) {
            return respond('Bu istemci bulunamadı!', 201);
        }

        if ($obj->delete()) {
            return respond('Başarıyla Silindi!');
        } else {
            return respond('Silinemedi!', 201);
        }
    }

    public function renew()
    {
        $obj = ExternalNotification::find(request('id'));
        if (!$obj) {
            return respond('Bu istemci bulunamadı!', 201);
        }
        $token = (string) Str::uuid();
        if (
            $obj->update([
                'token' => $token,
            ])
        ) {
            return respond(__('Token başarıyla yenilendi!') . "\n$token");
        }
    }

    public function edit()
    {
        $obj = ExternalNotification::find(request('id'));
        if (!$obj) {
            return respond('Bu istemci bulunamadı!', 201);
        }
        if ($obj->update(request()->all())) {
            return respond('İstemci Güncellendi!');
        } else {
            return respond('İstemci Güncellenemedi!', 201);
        }
    }

    /**
     * Accept external notification from an external service
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function accept(Request $request)
    {
        // TODO: Develop new notification system
    }

    public function create()
    {
        validate([
            'name' => 'required|max:32',
            'ip' => 'required|max:20',
        ]);

        $token = (string) Str::uuid();
        if (
            ExternalNotification::updateOrCreate(
                ['name' => request('name')],
                request()
                    ->merge(['token' => $token])
                    ->all()
            )
        ) {
            return respond(__('Token Oluşturuldu! ') . $token);
        } else {
            return respond('Token Oluşturulamadı!', 201);
        }
    }
}
