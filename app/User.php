<?php

namespace App;

use App\Models\Extension;
use App\Models\Permission;
use App\Models\Server;
use App\Models\UsesUuid;
use App\Support\Database\CacheQueryBuilder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * App\User
 *
 * @property-read mixed $id
 *
 * @method static Builder|User newModelQuery()
 * @method static Builder|User newQuery()
 * @method static Builder|User query()
 * @method static Builder|User find($value)
 */
class User extends Authenticatable
{
    use UsesUuid, Notifiable, CacheQueryBuilder;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'name',
        'username',
        'email',
        'password',
        'status',
        'forceChange',
        'objectguid',
        'auth_type',
        'last_login_at',
        'last_login_ip',
        'locale',
        'google2fa_secret'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = ['password', 'remember_token'];

    /** 
     * Interact with the user's OTP secret.
     *
     * @param  string  $value
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    protected function google2faSecret(): Attribute
    {
        return new Attribute(
            get: fn ($value) =>  !is_null($value) ? decrypt($value) : '',
            set: fn ($value) =>  !is_null($value) ? encrypt($value) : '',
        );
    }

    public function isAdmin()
    {
        // Very simply check status, this function created for more human like code write experience.
        return $this->status == 1;
    }

    public function servers()
    {
        return Server::get()->filter(function ($server) {
            return Permission::can(user()->id, 'server', 'id', $server->id);
        });
    }

    public function extensions()
    {
        return Extension::get()->filter(function ($extension) {
            return Permission::can(
                user()->id,
                'extension',
                'id',
                $extension->id
            );
        });
    }

    public function tokens()
    {
        return $this->hasMany('\App\Models\Token');
    }

    public function settings()
    {
        return $this->hasMany('\App\Models\UserSettings');
    }

    public function keys()
    {
        return $this->hasMany('\App\Models\ServerKey');
    }

    public function notifications()
    {
        return $this->hasMany('\App\Models\Notification');
    }

    public function favorites()
    {
        return $this->belongsToMany('\App\Models\Server', 'user_favorites')
            ->orderBy("created_at", "ASC")
            ->get()
            ->filter(function ($server) {
                return Permission::can(user()->id, 'server', 'id', $server->id);
            });
    }

    public function permissions()
    {
        return $this->morphMany('App\Models\Permission', 'morph');
    }

    public function roles()
    {
        return $this->belongsToMany('App\Models\Role', 'role_users');
    }

    public function accessTokens()
    {
        return $this->hasMany('\App\Models\AccessToken');
    }
}
