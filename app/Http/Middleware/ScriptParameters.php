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
        $extension = \App\Extension::where('name','like',$request->get('extension_name'))->first();
        $scripts = [];
        foreach ($extension->views['/'] as $script_name){
            array_push($scripts,Script::where('extensions','like',$request->get('extension_name'))->where('unique_code',$script_name)->first());
        }
        $request->request->add(['scripts' => $scripts]);
        return $next($request);
    }
}
