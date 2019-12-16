<?php

namespace App;

use App\Server;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * App\User
 *
 * @property-read mixed $id
 * @method static Builder|User newModelQuery()
 * @method static Builder|User newQuery()
 * @method static Builder|User query()
 * @method static Builder|User find($value)
 */
class User extends Authenticatable
{
    use UsesUuid, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password', 'status', 'forceChange', 'objectguid'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    public function isAdmin()
    {
        // Very simply check status, this function created for more human like code write experience.
        return $this->status == 1;
    }

    public function servers()
    {
        return Server::get()->filter(function($server){
            return Permission::can(user()->id,'server','id',$server->id);
        });
    }

    public function extensions()
    {
        return Extension::get()->filter(function($extension){
            return Permission::can(user()->id,'extension','id',$extension->id);
        });
    }

    public function widgets()
    {
        return $this->hasMany("\App\Widget");
    }

    public function tokens()
    {
        return $this->hasMany('\App\Token');
    }

    public function settings()
    {
        return $this->hasMany('\App\UserSettings');
    }

    public function notifications()
    {
        return $this->hasMany('\App\Notification');
    }

    public function favorites()
    {
        return $this->belongsToMany('\App\Server','user_favorites')->get()->filter(function($server){
            return Permission::can(user()->id,'server','id',$server->id);
        });
    }

    public function permissions()
    {
        return $this->morphMany('App\Permission', 'morph');
    }

    public function roles()
    {
        return $this->belongsToMany('App\Role', "role_users");
    }
}
