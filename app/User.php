<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Jenssegers\Mongodb\Auth\User as Authenticatable;

/**
 * App\User
 *
 * @property-read mixed $id
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection|\Illuminate\Notifications\DatabaseNotification[] $notifications
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User find($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\User where($value, $key)
 * @mixin \Eloquent
 */
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
        'name', 'email', 'password', 'status'
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

        // Check if this function called before in order to prevent more database calls.
        if($this->permissions == null){

            // Retrieve user permissions and set it into the User object.
            $this->permissions = Permission::get($this->_id);
        }

        // Return permissions just in case user don't want access object again.
        return $this->permissions;
    }

    public function isAdmin(){

        // Very simply check status, this function created for more human like code write experience.
        return $this->status == 1;
    }

    public function hasAccess($target,$id = null){

        // Ignore everything if user is Admin.
        if($this->isAdmin()){
            return true;
        }

        // Call function just in case.
        $this->permissions();

        // Check if user has access to target at all.
        if($this->permissions->__get($target) == null){
            return false;
        }

        // Lastly, check if user has permission for specific id of target.
        return in_array($id, $this->permissions->__get($target));
    }
}
