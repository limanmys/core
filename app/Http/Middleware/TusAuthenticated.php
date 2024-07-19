<?php

namespace App\Http\Middleware;

use Illuminate\Support\Facades\Log;
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
        if (auth('api')->check()) {
            return;
        }

        $token = "";
        if (request()->token) {
            $token = request()->token;
        } else if (request()->headers->get('Extension-Token')) {
            $token = request()->headers->get('Extension-Token');
        }

        if (! $token) {
            if (auth('api')->check()) {
                return true;
            }

            throw new UnauthorizedHttpException('', 'Extension-Token header is missing.');
        }

        Log::info('Extension-Token is valid. User ip: ' . request()->ip);
        return true;
    }
}
