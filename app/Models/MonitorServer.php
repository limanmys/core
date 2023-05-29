<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Server Monitor Model
 *
 * @extends Model
 */
class MonitorServer extends Model
{
    use UsesUuid;

    protected $fillable = [
        'last_checked', 'online', 'port', 'ip_address',
    ];
}
