<?php

namespace App\Http\Controllers;

use App\User;

class UserController extends Controller
{

    public function one(){
        $user = User::where('_id',\Auth::id())->first();
        return view('users.one',[
            "user" => $user
        ]);
    }


}
