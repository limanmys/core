<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;

class SettingsController extends Controller
{
    public function index(){
        return view('settings.index',[
            "users" => User::all(),
        ]);
    }
}
