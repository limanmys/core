<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Permission Data Model
 *
 * @extends Model
 */
class PermissionData extends Model
{
    use UsesUuid;

    protected $fillable = ['permission_id', 'data'];
}
