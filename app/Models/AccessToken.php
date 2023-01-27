<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Access Token Eloquent Model
 *
 * @extends Model
 */
class AccessToken extends Model
{
    use UsesUuid;

    protected $fillable = [
        'user_id',
        'token',
        'name',
        'last_used_at',
        'last_used_ip',
        'ip_range',
    ];
}
