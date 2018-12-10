<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

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
        //TODO
        // $permissions = request('permissions');
        // if($permissions->__get($type) == null || in_array($id,$permissions->__get($type)) == false){
        //     return false;
        // }
    }
}
