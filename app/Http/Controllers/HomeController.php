<?php

namespace App\Http\Controllers;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('index',[
            "stats" => shell_exec("screenfetch")
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

    public function new(){
        return view('permission.request');
    }

    public function all(){
        $requests = \App\LimanRequest::where('user_id',\Auth::id())->get();
        return view('permission.all',[
            "requests" => $requests
        ]);
    }

    public function request(){
        $req = new \App\LimanRequest();
        $req->user_id = \Auth::id();
        $req->email = \Auth::user()->email;
        $req->note = request('note');
        $req->type = request('type');
        $req->speed = request('speed');
        $req->status = 0;
        $req->save();
        return response('Talebiniz başarıyla alındı.',200);
    }
}
