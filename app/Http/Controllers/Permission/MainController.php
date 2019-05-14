<?php

namespace App\Http\Controllers\Permission;

use App\LimanRequest;
use App\User;
use App\Http\Controllers\Controller;

class MainController extends Controller
{
    public function all(){
        $requests = LimanRequest::all();
        foreach($requests as $request){
            $user = User::find($request->user_id);
            if(!$user){
                $request->user_name = "Kullanici Silinmis";
                $request->user_id = "";
            }else{
                $request->user_name = $user->name;
                $request->user_id = $user->_id;
            }

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
            switch ($request->speed){
                case "normal":
                    $request->speed = __("Normal");
                    break;
                case "urgent":
                    $request->speed = __("ACİL");
                    break;
            }
        }
        return view('permission.list',[
            "requests" => $requests
        ]);
    }

    public function one(){
        $request = LimanRequest::where('_id',request('permission_id'))->first();
        $request->user_name = User::where('_id',$request->user_id)->first()->name;
        return view('permission.requests.' . $request->type ,[
            "request" => $request
        ]);
    }

    public function requestUpdate()
    {
        $request = LimanRequest::where('_id',request('request_id'))->first();
        if(request('status') == "4"){
            $request->delete();
            return respond("Talep Silindi",200);
        }
        $request->status = request('status');
        $request->save();
        return respond("Talep Güncellendi",200);
    }
}
