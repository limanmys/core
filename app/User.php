<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Jenssegers\Mongodb\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Notifiable;
    protected $collection = 'users';
    protected $connection = 'mongodb';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    public function permissions(){
        $permissions = Permission::where('user_id',$this->_id)->first();
        return $permissions;
    }

    public function isAdmin(){
        return ($this->status == 1) ? true : false;
    }

    public function hasAccess($target,$id = null){
        if($this->isAdmin()){
            return true;
        }
        if(request('permissions')->__get($target) != null){
            if($id != null && in_array($id,request('permissions')->__get($target))){
                return true;
            }else if($id != null){
                return false;
            }
            return true;
        }else{
            return false;
        }
    }
}
