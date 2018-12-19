<?php

namespace App\Http\Controllers;

class PermissionController extends Controller
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
        $requests = \App\Request::all();
        $users = \App\User::all();
        foreach($requests as $r){
            $r->user_name = $users->where('_id',$r->user_id)->first()->name;
        }
        return view('permission.list',[
            "requests" => $requests
        ]);
    }

    public function one(){
        $request = \App\Request::where('_id',request('request_id'))->first();
        $request->user_name = \App\User::where('_id',$request->user_id)->first()->name;
        return view('permission.one',[
            "request" => $request
        ]);
    }
}