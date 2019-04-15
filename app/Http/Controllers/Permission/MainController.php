<?php

namespace App\Http\Controllers\Permission;

use App\LimanRequest;
use App\User;
use App\Http\Controllers\Controller;

class MainController extends Controller
{
    public function all(){
        $requests = LimanRequest::all();
        foreach($requests as $r){
            $r->user_name = User::find($r->user_id)->name;
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
}
