<?php

namespace App\Http\Controllers;

use App\Feature;
use Illuminate\Http\Request;

class FeatureController extends Controller
{
    public function index(){
        if(!Feature::where('name',\request('feature'))->exists()){
            return redirect(route('home'));
        }
        return view('feature.index');
    }
}
