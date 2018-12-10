<?php

namespace App\Http\Controllers;

use App\User;

class UserController extends Controller
{
    public function index(){
        return view('users.index',[
            "users" => User::all(),
        ]);
    }

    public function setLocale(){
        $languages = ["tr","en"];
        if(request()->has('locale') && in_array(request('locale'),$languages)){
            \Session::put('locale', request('locale'));
            return response('Alright',200);
        }else{
            return response('Language not found',404);
        }
    }
}
