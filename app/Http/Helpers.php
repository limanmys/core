<?php

if(!function_exists('respond')){
    function respond($message, $status = 200){
        return response([
            "message" => __($message)
        ],$status);
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