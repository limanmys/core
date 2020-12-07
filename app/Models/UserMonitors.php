<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserMonitors extends Model
{
    use UsesUuid;

    protected $fillable = [
        "user_id", "server_monitor_id", "name"
    ];
}
