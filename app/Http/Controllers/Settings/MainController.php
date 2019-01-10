<?php

namespace App\Http\Controllers\Settings;

use App\User;
use App\Http\Controllers\Controller;

class MainController extends Controller
{

    public function __construct()
    {
        $this->middleware('admin');
    }

    public function index(){
        return view('settings.index',[
            "users" => User::all(),
        ]);
    }
}
