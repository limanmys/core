<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PermissionManager
{
    // Main Function of Middleware
    public function handle($request, Closure $next)
    {

        // Ignore this middleware if user is not authenticated at all.
        if(!\Auth::check()){
            return $next($request);
        }
        $request->request->add(['user_id' => \Auth::id()]);

        // Get User Permissions.
        $permissions = \Auth::user()->permissions();

        // If there's not any permission and user is not admin, throw error.
        if($permissions == null && Auth::user()->isAdmin() == false){
            return $this->response("Liman'ı Kullanmak için hiçbir yetkiniz bulunmamaktadır.");
        }

        // Add permissions to request so we can access it later without accessing database again.
        $request->request->add(['permissions' => $permissions]);

        // If simply admin, allow request.
        if(\Auth::user()->isAdmin()){
            return $next($request);    
        }

        // Possible Inputs to Verify
        $validations = [
            "script" => 0,
            "server" => 1,
            "extension" => 1
        ];

        // Loop through every validations
        foreach($validations as $target=>$type){
            if($this->verify($target,$type) == false){
                return $this->response();
            }
        }
        
        // Verify Controller for double Check
        if($this->verifyController() == false){
            return $this->response();
        }
        
        // Process request if everything is ok.
        return $next($request);
    }

    private function response(){
        if(\request()->wantsJson()){
            return response()->json([
                "message" => "Liman: Bu işlem için yetkiniz bulunmamaktadır."
            ],403);
        }else{
            return response()->view('general.error',[
                "message" => "Liman: Bu işlem için yetkiniz bulunmamaktadır."
            ],403);
        }
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

    // Simply get Controller name from request headers.
    private function controller(){
        $controller = explode('\\',\request()->route()->getAction('controller'));
        $controller = explode('Controller@',$controller[count($controller) -1 ])[0];
        return strtolower($controller);
    }

    // Check permissions object' arrays if target key exists.
    private function checkArray($target){
        $permissions = (\request('permissions'));
        if($permissions->__get($target) == null || is_array($permissions->__get($target)) == false){
            return false;
        }else{
            return $permissions->__get($target);
        }
    }

    // Simply go through all functions to check permission.
    private function verify($target,$type){
        $permissions = \request('permissions');
        $name = $this->nameToId($target);
        if($name != null){
            if($arr = $this->checkArray($target)){
                if($arr == false | in_array($name,$arr) == (($type == 1) ? false : true)){
                    return false;
                }
            }else{
                return false;
            }
        }
        // TODO add objects to request here.
        return true;
    }

    // Check if Controller is in ignored list.
    private function verifyController(){
        $controller = $this->controller();
        $ignore_list = ["home","ssh","server","notification","auth"];
        if($this->checkArray($controller) == false && in_array($controller,$ignore_list) == false){
            return false;
        }else{
            return true;
        }
    }
}
