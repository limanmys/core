<?php

namespace App\Http\Controllers\API\Settings;

use App\Http\Controllers\Controller;
use App\Models\ExternalNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class NotificationController extends Controller
{
    public function externalNotifications()
    {
        $externalNotifications = ExternalNotification::all();

        return response()->json($externalNotifications);
    }

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
            'status' => (bool) $externalNotification,
            'token' => $token
        ], (bool) $externalNotification ? 200 : 500);
    }

    public function deleteExternalNotification(Request $request)
    {
        $externalNotification = ExternalNotification::find($request->id);

        if (!$externalNotification) {
            return response()->json([
                'status' => false
            ], 404);
        }

        $status = $externalNotification->delete();
        return response()->json([
            'status' => $status
        ], $status ? 200 : 500);
    }
}
