<?php

namespace App\Http\Middleware;

use App\Models\Certificate;
use Closure;
use Illuminate\Http\RedirectResponse;

/**
 * Server Middleware
 */
class Server
{
    /**
     * Handles requests that we make on server dependent pages
     *
     * This middleware checks if server has certificate and is online
     *
     * @param $request
     * @param Closure $next
     * @return RedirectResponse|mixed
     */
    public function handle($request, Closure $next)
    {
        if (
            in_array(server()->control_port, knownPorts()) &&
            ! Certificate::where([
                'server_hostname' => strtolower((string) server()->ip_address),
                'origin' => server()->control_port,
            ])->exists()
        ) {
            $message = __(
                ':server_name isimli sunucu için gerekli SSL sertifikası henüz eklenmemiş!',
                [
                    'server_name' => server()->name . '(' . server()->ip_address . ')',
                ]
            );
            abort(504, $message);

            return redirect()
                ->back()
                ->withErrors([
                    'message' => $message,
                ]);
        }
        $status = @fsockopen(
            server()->ip_address,
            server()->control_port,
            $errno,
            $errstr,
            intval(config('liman.server_connection_timeout')) / 1000
        );
        if (is_resource($status) || server()->control_port == -1) {
            return $next($request);
        } else {
            $message = __(':server_name isimli sunucuya erişim sağlanamadı!', [
                'server_name' => server()->name . '(' . server()->ip_address . ')',
            ]);
            abort(504, $message);

            return redirect()
                ->back()
                ->withErrors([
                    'message' => $message,
                ]);
        }
    }
}
