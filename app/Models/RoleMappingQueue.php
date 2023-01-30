<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Role Mapping Queue Model
 *
 * @extends Model
 */
class RoleMappingQueue extends Model
{
    use HasFactory, UsesUuid;

    protected $fillable = [
        'objectguid',
        'role_id',
    ];
}
