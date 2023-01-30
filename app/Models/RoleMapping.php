<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Role Mapping Model
 *
 * @extends Model
 */
class RoleMapping extends Model
{
    use UsesUuid;

    protected $fillable = ['role_id', 'group_id', 'dn'];

    /**
     * @return BelongsTo
     */
    public function role()
    {
        return $this->belongsTo('App\Models\Role');
    }
}
