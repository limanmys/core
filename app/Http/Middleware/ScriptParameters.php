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
        $extension = \App\Extension::where('name','like',$request->get('extension'))->first();
        $scripts = [];
        foreach ($extension->views[request('url')] as $script_name){
            array_push($scripts,Script::where('extensions','like',$request->get('extension'))->where('unique_code',$script_name)->first());
        }
        $request->request->add(['scripts' => $scripts]);
        return $next($request);
    }
}
