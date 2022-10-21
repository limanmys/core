<?php

namespace App\Http\Middleware;

use App\Models\AdminNotification;
use App\Models\Certificate;
use Closure;

class Extension
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Check Extension SSL ports to validate.
        $server = server();
        if (empty(extension()->sslPorts)) {
            return $next($request);
        }
        $ports = explode(',', (string) extension()->sslPorts);
        foreach ($ports as $port) {
            if (
                Certificate::where([
                    'server_hostname' => strtolower((string) $server->ip_address),
                    'origin' => trim($port),
                ])->exists()
            ) {
                continue;
            }
            AdminNotification::create([
                'title' => json_encode([
                    'tr' => __('Yeni Sertifika Onayı', [], 'tr'),
                    'en' => __('Yeni Sertifika Onayı', [], 'en'),
                ]),
                'type' => 'cert_request',
                'message' => $server->ip_address.':'.trim($port).':'.$server->id,
                'level' => 3,
            ]);

            return redirect()
                ->back()
                ->withErrors([
                    'message' => __('Bu sunucu ilk defa eklendiğinden dolayı bağlantı sertifikası yönetici onayına sunulmuştur. Bu sürede bu sunucu ile eklentiye erişemezsiniz.'),
                ]);
        }

        return $next($request);
    }
}
