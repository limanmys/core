<?php

namespace App\Http\Middleware;

use TusPhp\Request;
use TusPhp\Response;
use TusPhp\Middleware\TusMiddleware;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class TusAuthenticated implements TusMiddleware
{

    public function handle(Request $request, Response $response)
    {
        if (!auth()->check()) {
            throw new UnauthorizedHttpException('Kullanıcı giriş yapmadı!');
        }
    }
}
