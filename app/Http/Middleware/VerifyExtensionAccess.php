<?php

namespace App\Http\Middleware;

use App\Models\Extension;
use App\Models\Permission;
use App\Models\User;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Authenticate the request and verify that the caller is authorized
 * to access the target extension.
 *
 * Resolves the user from any of the supported token sources (JWT
 * Authorization header, web session, Extension-Token header/body, or
 * the "token" cookie) so that both admin-API and embedded-sandbox
 * callers are handled without breaking either.
 */
class VerifyExtensionAccess
{
    /**
     * @param  Closure(Request): (Response|RedirectResponse)  $next
     * @return mixed
     *
     * @throws HttpException
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $this->resolveUser($request);

        if (! $user) {
            throw new HttpException(401, 'Yetkisiz erişim.');
        }

        $extensionId = $request->headers->get('extension-id')
            ?: $request->input('extension_id');

        if (! $extensionId) {
            throw new HttpException(400, 'Eklenti belirtilmedi.');
        }

        $extension = Extension::find($extensionId);

        if (! $extension) {
            throw new HttpException(404, 'Eklenti bulunamadı.');
        }

        if (! Permission::can($user->id, 'extension', 'id', $extensionId)) {
            throw new HttpException(403, 'Bu işlem için yetkiniz bulunmamaktadır.');
        }

        $request->attributes->set('extension', $extension);

        return $next($request);
    }

    /**
     * Resolve the authenticated user from any supported token source.
     *
     * @return User|null
     */
    private function resolveUser(Request $request)
    {
        if (auth('api')->check()) {
            return auth('api')->user();
        }

        if (auth('web')->check()) {
            auth('api')->login(auth('web')->user());

            return auth('api')->user();
        }

        $token = $request->input('token')
            ?: $request->headers->get('Extension-Token');

        if ($token) {
            $request->headers->set('Authorization', 'Bearer '.$token);
            if (auth('api')->check()) {
                return auth('api')->user();
            }
        }

        if ($request->hasCookie('token')) {
            $request->headers->set(
                'Authorization',
                'Bearer '.$request->cookie('token')
            );
            if (auth('api')->check()) {
                return auth('api')->user();
            }
        }

        return null;
    }
}
