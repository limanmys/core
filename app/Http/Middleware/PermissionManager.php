<?php

namespace App\Http\Middleware;

use App\Permission;
use Closure;

class PermissionManager
{
    public static $verify = [
        "extension" , "script", "server"
    ];

    public static $except = [

    ];

    // Main Function of Middleware
    public function handle($request, Closure $next)
    {
        // Get User Permissions
        $request->request->add(['permissions' => Permission::get(\Auth::id())]);

        // If user is admin, allow request.
        if(\Auth::user()->isAdmin()){
            return $next($request);
        }

        // Loop through every validations
        foreach(PermissionManager::$verify as $target){
            if(!$this->check($target)){
                return respond('Liman: Bu işlem için yetkiniz bulunmamaktadır.',403);
            }
        }
        // Process request if everything is ok.
        return $next($request);
    }

    // Since id can be inside both in route and request headers, extract that information.
    private function nameToId($target){
        $name = null;
        if(request()->route($target . '_id') != null){
            $name = request()->route($target . '_id');
        }else if(request()->has($target . '_id')){
            $name = request()->get($target . '_id');
        }
        return $name;
    }

    private function check($target){
        $value = $this->nameToId($target);
        if($value == null){
            return true;
        }
        echo $value;
        if(!in_array($value, request('permissions')->__get($target))){
            return false;
        }
        return true;
    }

}
