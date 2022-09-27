<?php

namespace App\Http\Middleware;

use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use TusPhp\Middleware\TusMiddleware;
use TusPhp\Request;
use TusPhp\Response;

class TusAuthenticated implements TusMiddleware
{
    public function handle(Request $request, Response $response)
    {
        if (! auth()->check()) {
            throw new UnauthorizedHttpException('Kullanıcı giriş yapmadı!');
        }
    }
}
