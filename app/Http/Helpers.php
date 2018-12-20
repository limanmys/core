<?php

if(!function_exists('respond')){
    function respond($data){

        return response($data);
    }
}

if(!function_exists('controller')){
    function isCurrentPage($target = null,$extra = null){
        $controller = explode('\\',\request()->route()->getAction('controller'));
        $controller = explode('Controller@',$controller[count($controller) -1 ])[0];
        $controller = strtolower($controller);
        if($target != null){
            if($extra != null){
                return ($controller == $target) && request()->route( $controller . '_id') == $extra;
            }
            return $controller == $target;
        }
        return $controller;
    }
}