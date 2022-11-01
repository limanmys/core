<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Auth;

class SessionTimeout
{
    /**
     * Instance of Session Store
     *
     * @var session
     */
    protected $session;

    /**
     * Time for user to remain active, set to 900secs( 15minutes )
     *
     * @var timeout
     */
    protected $timeout = 900;

    public function __construct(Store $session)
    {
        $this->session = $session;
        $this->redirectUrl = route('login');
        $this->sessionLabel = 'warning';
        $this->lifetime = config('session.lifetime');
        $this->exclude = ['user_notifications', 'server_check'];
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (! Auth::check()) {
            $this->session->forget('lastActivityTime');

            return $next($request);
        }
        if (! $this->session->has('lastActivityTime')) {
            $this->session->put('lastActivityTime', time());
        } elseif (
            time() - $this->session->get('lastActivityTime') >
            $this->getTimeOut()
        ) {
            $this->session->forget('lastActivityTime');
            Auth::logout();
            $message = __(
                ':timeout dakika boyunca aktif olmadığınız için oturumunuz sonlandırıldı.',
                ['timeout' => $this->getTimeOut() / 60]
            );
            if ($request->wantsJson()) {
                $this->session->flash($this->getSessionLabel(), $message);

                return respond($this->getRedirectUrl(), 300);
            } else {
                return redirect($this->getRedirectUrl())->with([
                    $this->getSessionLabel() => $message,
                ]);
            }
        }
        if (! in_array($request->route()->getName(), $this->exclude)) {
            $this->session->put('lastActivityTime', time());
        }

        return $next($request);
    }

    /**
     * Get timeout from laravel default's session lifetime, if it's not set/empty, set timeout to 15 minutes
     *
     * @return int
     */
    private function getTimeOut()
    {
        return $this->lifetime * 60 ?: $this->timeout;
    }

    /**
     * Get redirect url from env file
     *
     * @return string
     */
    private function getRedirectUrl()
    {
        return env('SESSION_TIMEOUT_REDIRECTURL') ?: $this->redirectUrl;
    }

    /**
     * Get Session label from env file
     *
     * @return string
     */
    private function getSessionLabel()
    {
        return env('SESSION_LABEL') ?: $this->sessionLabel;
    }
}
