<?php

namespace App\Http\Controllers;

use App\LimanRequest;
use App\Server;
use App\Widget;

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
        system_log(7,"HOMEPAGE");
        $widgets = Widget::where('user_id',auth()->id())->get();
        foreach($widgets as $widget){
            $widget->server_name = Server::where('_id',$widget->server_id)->first()->name;
        }
        return view('index',[
            "widgets" => $widgets
        ]);
    }

    public function setLocale(){
        system_log(7,"SET_LOCALE");
        $languages = ["tr","en"];
        if(request()->has('locale') && in_array(request('locale'),$languages)){
            \Session::put('locale', request('locale'));
            return response('Alright',200);
        }else{
            return response('Language not found',404);
        }
    }

    public function collapse()
    {
        if(\Session::has('collapse')){
            \Session::remove('collapse');
        }else{
            \Session::put('collapse','');
        }
        return respond('Ok',200);
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
        $requests = LimanRequest::where('user_id',auth()->id())->get();
        foreach ($requests as $request){
            switch ($request->status){
                case "0":
                    $request->status = __("Talep Alındı");
                    break;
                case "1":
                    $request->status = __("İşleniyor");
                    break;
                case "2":
                    $request->status = __("Tamamlandı.");
                    break;
                case "3":
                    $request->status = __("Reddedildi.");
                    break;
                default:
                    $request->status = __("Bilinmeyen.");
                    break;
            }
        }
        return view('permission.all',[
            "requests" => $requests
        ]);
    }

    public function request(){
        $req = new LimanRequest();
        $req->user_id = auth()->id();
        $req->email = auth()->user()->email;
        $req->note = request('note');
        $req->type = request('type');
        $req->speed = request('speed');
        $req->status = 0;
        $req->save();
        return respond('Talebiniz başarıyla alındı.',200);
    }
    
}