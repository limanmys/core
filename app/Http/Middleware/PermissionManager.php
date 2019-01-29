<?php

namespace App\Http\Middleware;

use Closure;

class PermissionManager
{
    // Verify those values if request have either in request url or body.
    protected $verify = [
        "extension" , "script", "server"
    ];

    // Main Function of Middleware
    public function handle($request, Closure $next)
    {
        // Get User Permissions
        $request->request->add(['permissions' => \App\Permission::get(\Auth::id())]);

        // If user is admin, allow request.
        if(\Auth::user()->isAdmin()){
            $this->initializeObjects();
            return $next($request);
        }

        // Loop through every validations
        foreach($this->verify as $target){
            if(!$this->check($target)){
                return respond('Bu işlem için yetkiniz bulunmamaktadır.',403);
            }
        }

        $this->initializeObjects();

        // Process request if everything is ok.
        return $next($request);
    }

    private function check($target){

        //Let's get value from request parameters.
        $value = request($target . "_id");

        // If request don't have parameter in request, simply ignore permissions.
        if($value == null){
            return true;
        }

        // Check if specific id exists in permissions.
        if(!request('permissions')->__isset($target) || !in_array($value, request('permissions')->__get($target))){
            return false;
        }

        // If everything is passed, allow.
        return true;
    }

    private function initializeObjects(){
        foreach($this->verify as $target) {
            request()->request->add([$target => getObject($target, request($target . '_id'))]);
        }
    }

}
