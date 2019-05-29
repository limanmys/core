<?php

namespace App\Http\Middleware;

use App\Script;
use Closure;
use Illuminate\Http\Request;

class ScriptParameters
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Retrieve Extension Object.
        $extension = extension();

        // Extract Url from Request
        $url = null;
        if($request->route('unique_code') != null){
            $url = $request->route('unique_code');
            $url = explode("&",$url)[0];
        }else if($request->has('url')){
            $url = $request->get('url');
        }


        // Create Object to group Scripts as Array
        $scripts = [];

        // Validate view exists, if not, move on.
        if(isset($extension->views[$url])){
            // Loop Through scripts of view and get model data.
            foreach ($extension->views[$url] as $script_name){
                array_push($scripts,Script::where('extensions','like',$extension->name)->where('unique_code',$script_name)->first());
            }
        }else{
            // If script doesn't have view, that means user is trying to run script alone. So get the script and put it in the array.
            array_push($scripts,Script::where('unique_code',$url)->first());
        }

        // Add those values to access it later if required.
        $request->request->add(['scripts' => $scripts]);
        $request->request->add(['extension' => $extension]);
        $request->request->add(['url' => $url]);

        // Forward Request
        return $next($request);
    }
}
