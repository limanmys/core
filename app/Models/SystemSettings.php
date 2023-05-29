<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * System Settings Model
 *
 * @extends Model
 */
class SystemSettings extends Model
{
    use UsesUuid;

    protected $fillable = [
        'key', 'data',
    ];
}
