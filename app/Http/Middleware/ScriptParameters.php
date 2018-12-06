<?php

namespace App\Http\Middleware;

use App\Script;
use Closure;

class ScriptParameters
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
        $extension_name = null;
        if($request->route('extension') != null){
            $extension_name = $request->route('extension');
        }else if($request->has('extension')){
            $extension_name = $request->get('extension');
        }
        $extension = \App\Extension::where('name','like',$extension_name)->first();
        $url = null;
        if($request->route('unique_code') != null){
            $url = $request->route('unique_code');
            $url = explode("&",$url)[0];
        }else if($request->has('url')){
            $url = $request->get('url');
        }
        $scripts = [];
        if(isset($extension->views[$url])){
            foreach ($extension->views[$url] as $script_name){
                array_push($scripts,Script::where('extensions','like',$extension_name)->where('unique_code',$script_name)->first());
            }
        }else{
            array_push($scripts,Script::where('unique_code',$url)->first());
        }

        $request->request->add(['scripts' => $scripts]);
        $request->request->add(['url' => $url]);
        return $next($request);
    }
}
