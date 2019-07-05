<?php

namespace App;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\DatabaseNotificationCollection;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\DB;

/**
 * App\User
 *
 * @property-read mixed $id
 * @property-read DatabaseNotificationCollection|DatabaseNotification[] $notifications
 * @method static Builder|User newModelQuery()
 * @method static Builder|User newQuery()
 * @method static Builder|User query()
 * @method static Builder|User find($value)
 */
class User extends Authenticatable
{
    use Notifiable;
    use UsesUuid;

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

//    public function permissions(){
//
//        // Check if this function called before in order to prevent more database calls.
//        if($this->permissions == null){
//
//            // Retrieve user permissions and set it into the User object.
//            $this->permissions = Permission::get($this->_id);
//        }
//
//        // Return permissions just in case user don't want access object again.
//        return $this->permissions;
//    }

    public function isAdmin()
    {
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

    public function servers()
    {
        if(auth()->user()->isAdmin()){
            return Server::all();
        }

        return Server::find(DB::table("permissions")->where("user_id",auth()->user()->id)
            ->whereNotNull("server_id")->pluck("server_id")->toArray());
    }

    public function permissions()
    {
        return $this->belongsToMany("App\Permission");
    }
}
