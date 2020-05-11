<?php

namespace App\Http\Middleware;

use App\Permission;
use Closure;
use Illuminate\Support\Facades\Gate;

class PermissionManager
{
    // Verify those values if request have either in request url or body.
    protected $verify = ["extension", "script", "server"];

    // Main Function of Middleware
    public function handle($request, Closure $next)
    {
        // Get User Permissions
        $request->request->add(['permissions' => auth()->user()->permissions]);

        // If user is admin, allow request.
        if (
            auth()
                ->user()
                ->isAdmin() ||
            env('LIMAN_RESTRICTED') == true
        ) {
            $this->initializeObjects();
            return $next($request);
        }

        // Loop through every validations
        foreach ($this->verify as $target) {
            if (!$this->check($target)) {
                return respond('Bu işlem için yetkiniz bulunmamaktadır.', 403);
            }
        }

        $this->initializeObjects();

        // Process request if everything is ok.
        return $next($request);
    }

    private function check($target)
    {
        //Let's get value from request parameters.
        $value = request($target . "_id");

        // If request don't have parameter in request, simply ignore permissions.
        if ($value == null) {
            return true;
        }
        return Permission::can(auth()->user()->id, $target, 'id', $value);
    }

    private function initializeObjects()
    {
        foreach ($this->verify as $target) {
            request()->request->add([
                $target => getObject($target, request($target . '_id')),
            ]);
        }
    }
}
