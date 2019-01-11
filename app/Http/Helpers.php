<?php

if(!function_exists('respond')){
    function respond($message, $status = 200){
        if(\request()->wantsJson()){
            return response()->json([
                "message" => __($message)
            ],$status);
        }else{
            return response()->view('general.error',[
                "message" => __($message)
            ],$status);
        }
    }
}

if(!function_exists('notifications')){
    function notifications(){
        return \App\Notification::where([
            "user_id" => \Auth::id(),
            "read" => false
        ])->orderBy('updated_at','desc')->take(5)->get();
    }
}

if(!function_exists('log')){
    function log($message){
        Log::info(Auth::id() . ":" . $message);
    }
}

if(!function_exists('server_log')){
    function server_log($server_id, $message){
        Log::info(Auth::id()  . ":" . $server_id . ":" . $message);
    }
}

if(!function_exists('server')){
    function server(){
        return request('server');
    }
}