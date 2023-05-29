<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Role Model
 *
 * @extends Model
 */
class Role extends Model
{
    use UsesUuid;

    protected $fillable = ['name'];

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
    public function users()
    {
        return $this->belongsToMany('App\User', 'role_users');
    }
}
