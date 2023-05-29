<?php

namespace App\Http\Middleware;

use App\Models\Permission;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * Permission Manager Middleware
 */
class PermissionManager
{
    // Verify those values if request have either in request url or body.
    protected $verify = ['extension', 'script', 'server'];

    // Main Function of Middleware

    /**
     * Handles if user is eligible to do this event
     *
     * @param $request
     * @param Closure $next
     * @return JsonResponse|Response|mixed
     */
    public function handle($request, Closure $next)
    {
        // Get User Permissions
        $request->request->add(['permissions' => auth()->user()->permissions]);

        // If user is admin, allow request.
        if (
            auth()
                ->user()
                ->isAdmin()
        ) {
            $this->initializeObjects();

            return $next($request);
        }

        // Loop through every validations
        foreach ($this->verify as $target) {
            if (! $this->check($target)) {
                return respond('Bu işlem için yetkiniz bulunmamaktadır.', 403);
            }
        }

        $this->initializeObjects();

        // Process request if everything is ok.
        return $next($request);
    }

    /**
     * Initializes objects
     *
     * @return void
     */
    private function initializeObjects()
    {
        foreach ($this->verify as $target) {
            request()->request->add([
                $target => getObject($target, request($target . '_id')),
            ]);
        }
    }

    /**
     * Check if user has this type of permission
     *
     * @param $target
     * @return true
     */
    private function check($target)
    {
        //Let's get value from request parameters.
        $value = request($target . '_id');

        // If request don't have parameter in request, simply ignore permissions.
        if ($value == null) {
            return true;
        }

        return Permission::can(auth()->user()->id, $target, 'id', $value);
    }
}
