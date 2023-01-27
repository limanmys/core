<?php

namespace App\Http\Controllers\Notification;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
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
        if (! $obj) {
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
        if (! $obj) {
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
        if (! $obj) {
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
        try {
            $channel = ExternalNotification::where('token', request('token'))->first();
            if (! $channel) {
                return response()->json([
                    'Not authorized, token missing',
                ], 403);
            }
            if (ip_in_range($request->ip(), $channel->ip) == false) {
                return response()->json([
                    'Not authorized, unacceptable ip block',
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'title' => 'required|max:120',
                'message' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'Not Acceptable, inputs invalid',
                ], 406);
            }

            $data = json_decode((string) $request->get('message'));

            if (isset($data->notifyUser)) {
                $user = User::where('email', $data->notifyUser)->firstOrFail();
                Notification::create([
                    'user_id' => $user->id,
                    'title' => json_encode([
                        'tr' => __('Dış Bildirim -> ', [], 'tr') . $request->get('title'),
                        'en' => __('Dış Bildirim -> ', [], 'en') . $request->get('title'),
                    ]),
                    'type' => 'external_notification',
                    'message' => json_encode([
                        'tr' => (isset($data->notification) ? $data->notification : $data->notification_tr) . '. ' . __('Kullanıcı', [], 'tr') . ': ' . $data->user . ' ' . __('Makine', [], 'tr') . ': ' . $data->machine,
                        'en' => (isset($data->notification) ? $data->notification : $data->notification_en) . '. ' . __('Kullanıcı', [], 'en') . ': ' . $data->user . ' ' . __('Makine', [], 'en') . ': ' . $data->machine,
                    ]),
                    'level' => 3,
                ]);
            } elseif (isset($data->notifyGroup)) {
                $role = Role::where('name', $data->notifyGroup)->firstOrFail();
                foreach ($role->users as $user) {
                    Notification::create([
                        'user_id' => $user->id,
                        'title' => json_encode([
                            'tr' => __('Dış Bildirim -> ', [], 'tr') . $request->get('title'),
                            'en' => __('Dış Bildirim -> ', [], 'en') . $request->get('title'),
                        ]),
                        'type' => 'external_notification',
                        'message' => json_encode([
                            'tr' => (isset($data->notification) ? $data->notification : $data->notification_tr) . '. ' . __('Kullanıcı', [], 'tr') . ': ' . $data->user . ' ' . __('Makine', [], 'tr') . ': ' . $data->machine,
                            'en' => (isset($data->notification) ? $data->notification : $data->notification_en) . '. ' . __('Kullanıcı', [], 'en') . ': ' . $data->user . ' ' . __('Makine', [], 'en') . ': ' . $data->machine,
                        ]),
                        'level' => 3,
                    ]);
                }
            } else {
                $message = $request->get('message');
                if (isJson($message) && (isset($data->notification) || isset($data->notification_tr) || isset($data->notification_en))) {
                    $message = json_encode([
                        'tr' => (isset($data->notification) ? $data->notification : $data->notification_tr) . '. ' . __('Kullanıcı', [], 'tr') . ': ' . $data->user . ' ' . __('Makine', [], 'tr') . ': ' . $data->machine,
                        'en' => (isset($data->notification) ? $data->notification : $data->notification_en) . '. ' . __('Kullanıcı', [], 'en') . ': ' . $data->user . ' ' . __('Makine', [], 'en') . ': ' . $data->machine,
                    ]);
                }
                AdminNotification::create([
                    'title' => json_encode([
                        'tr' => __('Dış Bildirim -> ', [], 'tr') . $request->get('title'),
                        'en' => __('Dış Bildirim -> ', [], 'en') . $request->get('title'),
                    ]),
                    'type' => 'external_notification',
                    'message' => $message,
                    'level' => 3,
                ]);
            }

            $channel->update([
                'last_used' => Carbon::now(),
            ]);

            return response()->json([
                'OK',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                $e->getMessage(),
            ]);
        }
    }

    public function create()
    {
        $token = (string) Str::uuid();
        if (
            ExternalNotification::create(
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
