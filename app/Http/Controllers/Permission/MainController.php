<?php

namespace App\Http\Controllers\Permission;

use App\User;
use Auth;
use App\Http\Controllers\Controller;

class MainController extends Controller
{
    public function grant(){
        // First check if user is has permission to give permission.
        if($this->ability(request('type'),request('id')) == false){
            abort('Not allowed!',403);
        }
    }

    private function ability($type,$id){
        if(Auth::user()->isAdmin()){
            return true;
        }
    }

    public function all(){
        $requests = \App\LimanRequest::all();
        foreach($requests as $r){
            $r->user_name = User::find($r->user_id)->name;
        }
        return view('permission.list',[
            "requests" => $requests
        ]);
    }

    public function one(){
        $request = \App\LimanRequest::where('_id',request('permission_id'))->first();
        $request->user_name = User::where('_id',$request->user_id)->first()->name;
        return view('permission.requests.' . $request->type ,[
            "request" => $request
        ]);
    }
}
