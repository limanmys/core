<?php

namespace App\Http\Controllers\Extension\Sandbox;

use App\Http\Controllers\Controller;
use App\Mail\ExtensionMail;
use App\Mail\TemplatedExtensionMail;
use App\Models\Extension;
use App\Models\Permission;
use App\Models\Server;
use App\Models\Token;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Internal Controller
 * This controller takes requests from extensions and processes them.
 */
class InternalController extends Controller
{
    /**
     * Creates a internal controller instance
     */
    public function __construct()
    {
        if (array_key_exists('SERVER_ADDR', $_SERVER)) {
            $this->checkPermissions();
        }
    }

    /**
     * Check permissions of extension
     *
     * @return void
     */
    private function checkPermissions()
    {
        if (
            request('system_token') ==
            file_get_contents('/liman/keys/service.key') &&
            $_SERVER['REMOTE_ADDR'] == '127.0.0.1'
        ) {
            return;
        }

        if ($_SERVER['SERVER_ADDR'] != $_SERVER['REMOTE_ADDR']) {
            system_log(5, 'EXTENSION_INTERNAL_NO_PERMISSION', [
                'extension_id' => extension()->id,
            ]);
            abort(403, 'Not Allowed');
        }
        ($token = Token::where('token', request('token'))->first()) or
        abort(403, 'Token gecersiz');
        auth()->loginUsingId($token->user_id);

        ($server = Server::find(request('server_id'))) or
        abort(404, 'Sunucu Bulunamadi');
        if (
            ! Permission::can($token->user_id, 'server', 'id', $server->id)
        ) {
            system_log(7, 'EXTENSION_NO_PERMISSION_SERVER', [
                'extension_id' => extension()->id,
                'server_id' => request('server_id'),
            ]);
            abort(504, 'Sunucu icin yetkiniz yok.');
        }
        ($extension = Extension::find(request('extension_id'))) or
        abort(404, 'Eklenti Bulunamadi');
        if (
            ! Permission::can(
                $token->user_id,
                'extension',
                'id',
                $extension->id
            )
        ) {
            system_log(7, 'EXTENSION_NO_PERMISSION_SERVER', [
                'extension_id' => extension()->id,
                'server_id' => request('server_id'),
            ]);
            abort(504, 'Eklenti için yetkiniz yok.');
        }

        request()->request->add(['server' => $server]);
        request()->request->add(['extension' => $extension]);
    }

    /**
     * Send mail from extension
     *
     * @return void
     */
    public function sendMail()
    {
        if (! (bool) env('MAIL_ENABLED', false)) return;

        $sendTo = [];
        $to = json_decode(request('to'));
        if (! is_array($to)) {
            $sendTo[] = $to;
        } else {
            $sendTo = $to;
        }

        $template = ExtensionMail::class;
        if ((bool) request('templated')) {
            $template = TemplatedExtensionMail::class;
        }

        foreach ($sendTo as $recipient) {
            Mail::to($recipient)->send(
                new $template(
                    request('subject'),
                    base64_decode((string) request('content')),
                    json_decode((string) request('attachments'), true),
                )
            );
        }
    }

    /**
     * Creates VNC token
     *
     * @return array|string|string[]
     */
    public function addProxyConfig()
    {
        if (! is_dir('/liman/keys/' . 'vnc')) {
            mkdir('/liman/keys/' . 'vnc', 0700);
        }
        $writer = fopen('/liman/keys/' . 'vnc/config', 'a+');
        $hostname = request('hostname');
        $port = request('port');
        $token = Str::uuid();
        $token = str_replace('-', '', (string) $token);
        fwrite($writer, $token . ": $hostname:$port" . "\n");

        return $token;
    }
}
