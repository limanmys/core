<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ExtensionsController extends Controller
{
    public function index(){
        return view('extensions.index');
    }
}
