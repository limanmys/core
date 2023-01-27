<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Liman Request
 *
 * @extends Model
 */
class LimanRequest extends Model
{
    use UsesUuid;

    protected $fillable = [
        'user_id',
        'status',
        'speed',
        'type',
        'note',
        'email',
    ];
}
