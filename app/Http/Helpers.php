<?php

if(!function_exists('respond')){
    function respond($data){
        return response($data);
    }
}

if(!function_exists('testing')){
    function testing(){
        return true;
    }
}