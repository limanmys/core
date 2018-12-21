<?php

if(!function_exists('respond')){
    function respond($data){

        return response($data);
    }
}

if(!function_exists('notifications')){
    function notifications(){
        return \App\Notification::where('user_id',\Auth::id())->orderBy('updated_at','desc')->take(5)->get();
    }
}