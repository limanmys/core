<?php

namespace App\Http\Controllers\API\Settings;

use App\Exceptions\JsonResponseException;
use App\Http\Controllers\Controller;
use App\Models\ExternalNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Notification Controller
 */
class NotificationController extends Controller
{
    /**
     * Get external notification list
     *
     * @return JsonResponse
     */
    public function externalNotifications()
    {
        return ExternalNotification::all();
    }

    /**
     * Create a new external notification channel
     *
     * @param Request $request
     * @return JsonResponse
     * @throws JsonResponseException
     */
    public function createExternalNotification(Request $request)
    {
        validate([
            'name' => 'required|max:32',
            'ip' => 'required|max:20',
        ]);

        $token = (string) Str::uuid();
        $externalNotification = ExternalNotification::create([
            'name' => $request->name,
            'ip' => $request->ip,
            'token' => $token
        ]);

        return response()->json([
            'message' => 'Dış bildirim ucu başarıyla oluşturuldu.',
            'token' => $token
        ], (bool) $externalNotification ? 200 : 500);
    }

    /**
     * Delete external notification channel
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function deleteExternalNotification(Request $request)
    {
        $externalNotification = ExternalNotification::find($request->id);

        if (! $externalNotification) {
            return response()->json([
                'status' => false
            ], 404);
        }

        $status = $externalNotification->delete();
        return response()->json([
            'message' => 'Dış bildirim ucu silindi.'
        ], $status ? 200 : 500);
    }
}
