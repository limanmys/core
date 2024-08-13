<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Role User Model
 *
 * @extends Model
 */
class RoleUser extends Model
{
    use UsesUuid;

    protected $fillable = ['role_id', 'user_id', 'type'];

    /**
     * @return BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

    /**
     * @return BelongsTo
     */
    public function role()
    {
        return $this->belongsTo('App\Models\Role');
    }
}
