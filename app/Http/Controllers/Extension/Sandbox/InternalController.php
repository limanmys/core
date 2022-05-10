<?php

namespace App\Http\Controllers\Extension\Sandbox;

use App\Models\Extension;
use App\Http\Controllers\Controller;
use App\Models\JobHistory;
use App\Models\Notification;
use App\Models\Permission;
use App\Models\Server;
use App\Models\Token;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Mail;
use App\Mail\ExtensionMail;

class InternalController extends Controller
{
    public function __construct()
    {
        if (array_key_exists("SERVER_ADDR", $_SERVER)) {
            $this->checkPermissions();
        }
    }

    public function sendMail()
    {
        Mail::to(request('to'))->send(
            new ExtensionMail(
                request('subject'),
                base64_decode(request('content')),
                json_decode(request('attachments'), true),
            )
        );
    }

    public function sendNotification()
    {
        Notification::new(
            request('title') . " (" . extension()->display_name . ")",
            request('type'),
            request('message')
        );
    }

    /**
     * @api {post} /lmn/private/reverseProxyRequest Add Vnc Proxy Config
     * @apiName SandboxAddVncProxyConfig
     * @apiGroup Sandbox
     *
     * @apiParam {String} hostname server host you wish to use in vnc.
     * @apiParam {String} port server port you wish to use in vnc.
     * @apiParam {String} server_id Target Server Id
     * @apiParam {String} extension_id Target Extension Id
     * @apiParam {String} token Authenticated User Token
     */
    public function addProxyConfig()
    {
        if (!is_dir("/liman/keys/" . "vnc")) {
            mkdir("/liman/keys/" . "vnc", 0700);
        }
        $writer = fopen("/liman/keys/" . "vnc/config", "a+");
        $hostname = request('hostname');
        $port = request('port');
        $token = Str::uuid();
        $token = str_replace("-", "", $token);
        fwrite($writer, $token . ": $hostname:$port" . "\n");
        return $token;
    }

    private function checkPermissions()
    {
        if (
            request('system_token') ==
                file_get_contents("/liman/keys/service.key") &&
            $_SERVER['REMOTE_ADDR'] == "127.0.0.1"
        ) {
            return;
        }

        if ($_SERVER['SERVER_ADDR'] != $_SERVER['REMOTE_ADDR']) {
            system_log(5, "EXTENSION_INTERNAL_NO_PERMISSION", [
                "extension_id" => extension()->id,
            ]);
            abort(403, 'Not Allowed');
        }
        ($token = Token::where('token', request('token'))->first()) or
            abort(403, "Token gecersiz");
        auth()->loginUsingId($token->user_id);

        ($server = Server::find(request('server_id'))) or
            abort(404, 'Sunucu Bulunamadi');
        if (
            !Permission::can($token->user_id, 'server', 'id', $server->id) &&
            env('LIMAN_RESTRICTED') != true
        ) {
            system_log(7, "EXTENSION_NO_PERMISSION_SERVER", [
                "extension_id" => extension()->id,
                "server_id" => request('server_id'),
            ]);
            abort(504, "Sunucu icin yetkiniz yok.");
        }
        ($extension = Extension::find(request('extension_id'))) or
            abort(404, 'Eklenti Bulunamadi');
        if (
            !Permission::can(
                $token->user_id,
                'extension',
                'id',
                $extension->id
            ) &&
            env('LIMAN_RESTRICTED') != true
        ) {
            system_log(7, "EXTENSION_NO_PERMISSION_SERVER", [
                "extension_id" => extension()->id,
                "server_id" => request('server_id'),
            ]);
            abort(504, "Eklenti için yetkiniz yok.");
        }

        request()->request->add(['server' => $server]);
        request()->request->add(['extension' => $extension]);
    }
}
