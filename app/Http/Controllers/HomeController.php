<?php

namespace App\Http\Controllers;

use App\Server;

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
        $servers = Server::all();
        return view('index',[
            "linux_count" => $servers->where('type','linux')->count(),
            "linux_ssh_count" => $servers->where('type','linux_ssh')->count(),
            "windows_count" => $servers->where('type','windows')->count(),
            "windows_powershell_count" => $servers->where('type','windows_powershell')->count(),
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

    public function setTheme(){
        if(\Session::has('dark_mode')){
            \Session::remove('dark_mode');
        }else{
            \Session::put('dark_mode','true');
        }
        return respond('Tema Guncellendi.');
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