<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use App\Extension;

class PermissionManager
{
    public function handle($request, Closure $next)
    {
        $permissions = Auth::user()->permissions();
        if($permissions == null && Auth::user()->isAdmin() == false){
            return $this->response("Liman'ı Kullanmak için hiçbir yetkiniz bulunmamaktadır.");
        }
        $request->request->add(['permissions' => $permissions]);

        if(Auth::user()->isAdmin()){
            return $next($request);    
        }

        $validations = [
            "script" => 0,
            "server" => 1,
            "extension" => 1
        ];
        
        foreach($validations as $target=>$type){
            if($this->verify($target,$type) == false){
                return $this->response();
            }
        }
        
        if($this->verifyController() == false){
            return $this->response();
        }
        
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

    private function extensionNametoId(){
        $name = null;
        if(request()->route($target . '_id') != null){
            $name = request()->route($target . '_id');
        }else if(request()->has($target . '_id')){
            $name = request()->get($target . '_id');
        }
    }

    private function controller(){
        $controller = explode('\\',\request()->route()->getAction('controller'));
        $controller = explode('Controller@',$controller[count($controller) -1 ])[0];
        return strtolower($controller);
    }

    private function checkArray($target){
        $permissions = (\request('permissions'));
        if($permissions->__get($target) == null || is_array($permissions->__get($target)) == false){
            return false;
        }else{
            return $permissions->__get($target);
        }
    }

    private function verify($target,$type){
        $permissions = (\request('permissions'));
        $id = null;
        if(request()->route($target . '_id') != null){
            $id = request()->route($target . '_id');
        }else if(request()->has($target . '_id')){
            $id = request()->get($target . '_id');
        }
        if($id != null){
            if($arr = $this->checkArray($target)){
                if(in_array($id,$arr) == false){
                    return false;
                }
            }else{
                return false;
            }
        }
        return true;
    }

    private function verifyController(){
        $controller = $this->controller();
        $ignore_list = ["home","ssh","server"];
        if($this->checkArray($controller) == false && in_array($controller,$ignore_list) == false){
            return false;
        }else{
            return true;
        }
    }
}
