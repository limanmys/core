<?php

namespace App\Http\Middleware;

use App\Models\Certificate;
use Closure;

/**
 * Extension
 * Checks if extension needs certificates on server
 */
class Extension
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
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
            if ((int) $port == 636) {
                if (env('LDAP_IGNORE_CERT', false)) {
                    return $next($request);
                }
            }

            if (
                Certificate::where([
                    'server_hostname' => strtolower((string) $server->ip_address),
                    'origin' => trim($port),
                ])->exists()
            ) {
                continue;
            }
            // TODO: New certificate notification

            return redirect()
                ->back()
                ->withErrors([
                    'message' => __('Bu sunucu ilk defa eklendiğinden dolayı bağlantı sertifikası yönetici onayına sunulmuştur. Bu sürede bu sunucu ile eklentiye erişemezsiniz.'),
                ]);
        }

        return $next($request);
    }
}
