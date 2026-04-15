<?php

namespace App\Http\Middleware;

use App\Models\Permission;
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
        // 1. Check if already authenticated via JWT Authorization header
        if (auth('api')->check()) {
            $this->checkExtensionPermission();
            return;
        }

        // 2. Check session/cookie-based auth (web guard)
        if (auth('web')->check()) {
            auth('api')->login(auth('web')->user());
            $this->checkExtensionPermission();
            return;
        }

        // 3. Try Extension-Token (JWT from sandbox customRequestData['token'])
        $token = "";
        if (request()->token) {
            $token = request()->token;
        } else if (request()->headers->get('Extension-Token')) {
            $token = request()->headers->get('Extension-Token');
        }

        if (! $token) {
            // 4. Try cookie-based JWT (web middleware group doesn't run CookieJWTAuthenticator)
            if (request()->hasCookie('token')) {
                request()->headers->set('Authorization', 'Bearer ' . request()->cookie('token'));
                if (auth('api')->check()) {
                    $this->checkExtensionPermission();
                    return;
                }
            }

            throw new UnauthorizedHttpException('', 'Extension-Token header is missing.');
        }

        // Validate the JWT token
        try {
            request()->headers->set('Authorization', 'Bearer ' . $token);
            if (! auth('api')->check()) {
                throw new UnauthorizedHttpException('', 'Invalid Extension-Token.');
            }
        } catch (\Exception $e) {
            throw new UnauthorizedHttpException('', 'Invalid Extension-Token.');
        }

        $this->checkExtensionPermission();

        Log::info('Extension-Token validated for user ' . auth('api')->user()->id . '. IP: ' . request()->ip());
        return true;
    }

    /**
     * Check if the authenticated user has permission to upload to the specified extension
     */
    private function checkExtensionPermission(): void
    {
        $extensionId = request()->headers->get('extension-id');

        if (! $extensionId) {
            return;
        }

        $user = auth('api')->user();
        if (! $user) {
            return;
        }

        if (! Permission::can($user->id, 'extension', 'id', $extensionId)) {
            throw new UnauthorizedHttpException('', 'You do not have permission to upload to this extension.');
        }
    }
}
