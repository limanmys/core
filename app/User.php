<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Jenssegers\Mongodb\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Notifiable;
    protected $collection = 'users';
    protected $connection = 'mongodb';
    public $permissions = null;
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
        if($this->permissions == null){
            $this->permissions = Permission::get($this->_id);
        }
        return $this->permissions;
    }

    public function isAdmin(){
        return $this->status == 1;
    }

    public function hasAccess($target,$id = null){
        if($this->permissions == null){
            $this->permissions = Permission::get($this->_id);
        }
        if($this->permissions->__get($target) == null){
            return $this->isAdmin();
        }
        return in_array($id, $this->permissions->__get($target));
    }
}
