<?php

namespace App\Models;

use App\Models\Extension;
use App\Models\Permission;
use App\Models\Server;
use App\Models\UsesUuid;
use App\Support\Database\CacheQueryBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject as JWTSubject;
use Illuminate\Support\Str;

/**
 * App\Models\User
 *
 * @property-read mixed $id
 *
 * @method static Builder|User newModelQuery()
 * @method static Builder|User newQuery()
 * @method static Builder|User query()
 * @method static Builder|User find($value)
 */
class User extends Authenticatable implements JWTSubject
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
        'google2fa_secret',
        'otp_enabled',
        'session_time'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = ['password', 'remember_token', 'google2fa_secret', 'keycloak_token', 'keycloak_refresh_token'];

    /**
     * Determines if user is admin or not
     *
     * @return bool
     */
    public function isAdmin()
    {
        // Very simply check status, this function created for more human like code write experience.
        return $this->status == 1;
    }

    public function scopeAdmins($query)
    {
        return $query->where('status', 1);
    }

    public function scopeNonAdmins($query)
    {
        return $query->where('status', 0);
    }

    /**
     * Get users servers inside of permission scope
     *
     * @return mixed
     */
    public function servers()
    {
        return Server::get()->filter(function ($server) {
            return Permission::can(user()->id, 'server', 'id', $server->id);
        });
    }

    /**
     * Get users extensions inside of permission scope
     *
     * @return mixed
     */
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

    public function notifications()
    {
        return $this->belongsToMany('App\Models\Notification', 'notification_users')
            ->withPivot('read_at');
    }

    /**
     * @return HasMany
     */
    public function settings()
    {
        return $this->hasMany('\App\Models\UserSettings');
    }

    /**
     * @return HasMany
     */
    public function keys()
    {
        return $this->hasMany('\App\Models\ServerKey');
    }

    public function myFavorites()
    {
        return $this->belongsToMany('\App\Models\Server', 'user_favorites');
    }

    /**
     * @return Collection
     */
    public function favorites()
    {
        return $this->belongsToMany('\App\Models\Server', 'user_favorites')
            ->orderBy("created_at", "ASC")
            ->get()
            ->filter(function ($server) {
                return Permission::can(user()->id, 'server', 'id', $server->id);
            });
    }

    /**
     * @return MorphMany
     */
    public function permissions()
    {
        return $this->morphMany('App\Models\Permission', 'morph');
    }

    /**
     * @return BelongsToMany
     */
    public function roles()
    {
        return $this->belongsToMany('App\Models\Role', 'role_users');
    }

    /**
     * @return HasMany
     */
    public function accessTokens()
    {
        return $this->hasMany('\App\Models\AccessToken');
    }

    /**
     * @return HasOne
     */
    public function oauth2Token()
    {
        return $this->hasOne('\App\Models\Oauth2Token', 'user_id');
    }

    /**
     * @return HasMany
     */
    public function authLogs()
    {
        return $this->hasMany('\App\Models\AuthLog');
    }

    /**
     * Assign role to user
     * 
     * @param string $id
     * @return void
     */
    public function assignRole($id)
    {
        // Check if id is valid for a role
        if (Role::find($id) == null) {
            return;
        }

        // Check if role is already assigned
        if ($this->roles()->where('role_id', $id)->exists()) {
            return;
        }

        $this->roles()->attach($id, ['id' => Str::uuid()]);
    }

    /**
     * Interact with the user's OTP secret.
     *
     * @param string $value
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    protected function google2faSecret(string $value = null): Attribute
    {
        return new Attribute(
            get: fn($value) => ! is_null($value) ? decrypt($value) : '',
            set: fn($value) => ! is_null($value) ? encrypt($value) : null,
        );
    }

    /**
     * Manipulate model's session time field
     */
    public function getSessionTimeAttribute($value) {
        if ($value == -1) {
            return env('JWT_TTL', 120);
        }

        return $value;
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier() {
        return $this->getKey();
    }
    
    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims() {
        return [];
    }    
}
