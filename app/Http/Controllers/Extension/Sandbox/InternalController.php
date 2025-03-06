<?php

namespace App\Http\Controllers\Extension\Sandbox;

use App\Http\Controllers\Controller;
use App\Mail\ExtensionMail;
use App\Mail\TemplatedExtensionMail;
use App\Models\Extension;
use App\Models\Permission;
use App\Models\Server;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Internal Controller
 * This controller takes requests from extensions and processes them.
 */
class InternalController extends Controller
{
    /**
     * Creates an internal controller instance
     */
    public function __construct()
    {
        if (isset($_SERVER['SERVER_ADDR'])) {
            $this->checkPermissions();
        }
    }

    /**
     * Check permissions of the extension
     *
     * @return void
     */
    private function checkPermissions()
    {
        $systemToken = request('system_token');
        $serviceKey = file_get_contents('/liman/keys/service.key');
        $remoteAddr = $_SERVER['REMOTE_ADDR'];
        $serverAddr = $_SERVER['SERVER_ADDR'];

        if ($systemToken === $serviceKey && $remoteAddr === '127.0.0.1') {
            return;
        }

        if ($serverAddr !== $remoteAddr) {
            $this->logAndAbort('EXTENSION_INTERNAL_NO_PERMISSION', 'Not Allowed', 403);
        }

        $userId = auth('api')->id();
        $token = request('token');

        if (! $token) {
            $this->logAndAbort('EXTENSION_NO_TOKEN', 'Token gecersiz', 403);
        } elseif (! auth('api')->check()) {
            $this->logAndAbort('EXTENSION_INVALID_TOKEN', 'Token gecersiz', 403);
        }

        $serverId = request('server_id');
        $server = Server::find($serverId);
        if (! $server) {
            abort(404, 'Sunucu Bulunamadi');
        }

        if (! Permission::can($userId, 'server', 'id', $server->id)) {
            $this->logAndAbort('EXTENSION_NO_PERMISSION_SERVER', 'Sunucu icin yetkiniz yok.', 504, $serverId);
        }

        $extensionId = request('extension_id');
        $extension = Extension::find($extensionId);
        if (! $extension) {
            abort(404, 'Eklenti Bulunamadi');
        }

        if (! Permission::can($userId, 'extension', 'id', $extension->id)) {
            $this->logAndAbort('EXTENSION_NO_PERMISSION_SERVER', 'Eklenti için yetkiniz yok.', 504, $serverId);
        }

        request()->request->add(['server' => $server, 'extension' => $extension]);
    }

    /**
     * Log the error and abort the request with a response
     *
     * @param  string  $logCode
     * @param  string  $message
     * @param  int  $statusCode
     * @param  mixed  $additionalData
     * @return void
     */
    private function logAndAbort($logCode, $message, $statusCode = 403, $additionalData = null)
    {
        $logData = [
            'extension_id' => extension()->id,
        ];

        if ($additionalData) {
            $logData = array_merge($logData, ['server_id' => $additionalData]);
        }

        system_log(5, $logCode, $logData);
        abort($statusCode, $message);
    }

    /**
     * Send mail from extension
     *
     * @return void
     */
    public function sendMail()
    {
        if (! (bool) env('MAIL_ENABLED', false)) {
            return;
        }

        $to = json_decode(request('to'));
        $recipients = is_array($to) ? $to : [$to];

        $templateClass = (bool) request('templated') ? TemplatedExtensionMail::class : ExtensionMail::class;
        $subject = request('subject');
        $content = base64_decode(request('content'));
        $attachments = json_decode(request('attachments'), true);

        foreach ($recipients as $recipient) {
            Mail::to($recipient)->send(new $templateClass($subject, $content, $attachments));
        }
    }

    /**
     * Creates VNC token
     *
     * @return string
     */
    public function addProxyConfig()
    {
        $vncDir = '/liman/keys/vnc';

        if (! is_dir($vncDir)) {
            mkdir($vncDir, 0700, true);
        }

        $configFilePath = $vncDir.'/config';
        $writer = fopen($configFilePath, 'a+');

        $hostname = request('hostname');
        $port = request('port');
        $token = str_replace('-', '', (string) Str::uuid());

        fwrite($writer, "$token: $hostname:$port\n");
        fclose($writer);

        return $token;
    }

    public function getLimanUsers()
    {
        return json_encode(User::select('id', 'name', 'username', 'email', 'status')->get());
    }
}
