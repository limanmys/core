<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use App\Extension;

class PermissionManager
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $permissions = Auth::user()->permissions();
        if($permissions == null && Auth::user()->isAdmin() == false){
            abort(403,__("Liman'ı Kullanmak için hiçbir yetkiniz bulunmamaktadır."));
        }
        $request->request->add(['permissions' => $permissions]);
        if(Auth::user()->isAdmin()){
            return $next($request);    
        }
        
        $controller = explode('\\',$request->route()->getAction('controller'));
        $controller = $controller[count($controller) -1 ];
        
        $server_id = null;
        if($request->route('server_id') != null){
            $server_id = $request->route('server_id');
        }else if($request->has('server_id')){
            $server_id = $request->get('server_id');
        }
        if($server_id != null){
            if($permissions->servers == null || is_array($permissions->servers) == false){
                abort(403,__("Sunuculara erişmek için yetkiniz bulunmamaktadır."));
            }
            if(in_array($server_id,$permissions->servers) == false){
                return response()->view('general.error',[
                    "message" => "Liman : Bu sunucuya erişmek için yetkiniz bulunmamaktadır."
                ]);
            }
        }

        $script_id = null;
        if($request->route('script_id') != null){
            $script_id = $request->route('script_id');
        }else if($request->has('script_id')){
            $script_id = $request->get('script_id');
        }
        if($script_id != null){
            if($permissions->scripts == null || is_array($permissions->scripts) == false){
                return response()->view('general.error',[
                    "message" => "Liman : Betikleri kullanabilmek için yetkiniz bulunmamaktadır."
                ]);
            }
            if(in_array($script_id,$permissions->scripts) == true){
                return response()->view('general.error',[
                    "message" => "Liman : Bu betiğe erişmek için yetkiniz bulunmamaktadır."
                ]);
            }
        }

        $extension_id = null;
        if($request->route('extension_id') != null){
            $extension_id = $request->route('extension_id');
        }else if($request->has('extension_id')){
            $extension_id = $request->get('extension_id');
        }else if($request->route('extension') != null){
            $extension_id = Extension::where('name','like',$request->route('extension'))->first()->_id;
        }else if($request->has('extension')){
            $extension_id = Extension::where('name','like',$request->get('extension'))->first()->_id;
        }
        if($extension_id != null){
            if($permissions->extensions == null || is_array($permissions->extensions) == false){
                return response()->view('general.error',[
                    "message" => "Liman : Eklentileri kullanabilmek için yetkiniz bulunmamaktadır."
                ]);
            }
            if(in_array($extension_id,$permissions->extensions) != true){
                return response()->view('general.error',[
                    "message" => "Liman : Bu eklentiye erişmek için yetkiniz bulunmamaktadır."
                ]);
            }
        }
        
        return $next($request);
    }
}
