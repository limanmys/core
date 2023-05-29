<?php

namespace App\Http\Middleware;

use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use TusPhp\Middleware\TusMiddleware;
use TusPhp\Request;
use TusPhp\Response;

/**
 * Check if user is logged in when using TUS
 *
 * @extends TusMiddleware
 */
class TusAuthenticated implements TusMiddleware
{
    /**
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function handle(Request $request, Response $response)
    {
        if (! auth()->check()) {
            throw new UnauthorizedHttpException('Kullanıcı giriş yapmadı!');
        }
    }
}
