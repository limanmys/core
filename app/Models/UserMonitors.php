<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * User Monitors Model
 *
 * @extends Model
 */
class UserMonitors extends Model
{
    use UsesUuid;

    protected $fillable = [
        'user_id', 'server_monitor_id', 'name',
    ];
}
