<?php

namespace App\Http\Middleware;

use Closure;

/**
 * Touch Server Middleware
 */
class TouchServer
{
    /**
     * Handle an incoming request.
     *
     * Bu middleware en son bakılan sunucuya dokunur ve sürekli SQL sorgusu atılmaması için
     * onu session içerisine yerleştirir. Başka bir sunucuya bakılana kadar güncelleme için
     * sorgu atılmaz.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ($request->server_id && auth()->check()) {
            if (
                ($request->session()->get('last_touched')
                    && $request->server_id != $request->session()->get('last_touched'))
                || ! $request->session()->get('last_touched')
            ) {
                try {
                    \App\Models\Server::where('id', $request->server_id)->firstOrFail()->touch();
                    $request->session()->put('last_touched', $request->server_id);
                } catch (\Throwable) {
                    return $next($request);
                }
            }
        }

        return $next($request);
    }
}
