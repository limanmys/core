<?php

namespace App\Http\Controllers\API\Settings;

use App\Http\Controllers\Controller;
use App\Mail\TestMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;

class MailController extends Controller
{
    /**
     * Get existing configuration
     *
     * @return JsonResponse
     */
    public function getConfiguration()
    {
        return response()->json([
            'active' => (bool) env('MAIL_ENABLED', 'false'),
            'host' => env('MAIL_HOST'),
            'port' => env('MAIL_PORT'),
            'username' => env('MAIL_USERNAME'),
            'encryption' => env('MAIL_ENCRYPTION'),
        ]);
    }

    /**
     * Save new mail configuration
     *
     * @param Request $request
     * @return JsonResponse
     * @throws GuzzleException
     */
    public function saveConfiguration(Request $request)
    {
        validate([
            'host' => 'required|string',
            'port' => 'required|integer',
            'username' => 'required|string',
            'password' => 'nullable|string',
            'encryption' => 'required|string',
        ]);

        setEnv([
            'MAIL_ENABLED' => (bool) $request->active,
            'MAIL_HOST' => $request->host,
            'MAIL_PORT' => $request->port,
            'MAIL_USERNAME' => $request->username,
            'MAIL_ENCRYPTION' => $request->encryption,
        ]);

        if ($request->password) {
            setEnv([
                'MAIL_PASSWORD' => $request->password,
            ]);
        }

        // Force update the config at runtime to ensure the mailer uses the new values
        Config::set('mail.host', $request->host);
        Config::set('mail.port', $request->port);
        Config::set('mail.username', $request->username);
        Config::set('mail.encryption', $request->encryption);
        if ($request->password) {
            Config::set('mail.password', $request->password);
        }

        try {
            Mail::to($request->username)->send(
                new TestMail('Test Mail', __('Liman MYS test mail gönderimi.'))
            );
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Ayarlar kaydedildi ancak test maili gönderilemedi. ' . $e->getMessage()
            ], 500);
        }

        return response()->json([
            'message' => 'Ayarlar kaydedildi.',
        ]);
    }
}
