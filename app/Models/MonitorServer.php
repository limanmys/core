<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonitorServer extends Model
{
    use UsesUuid;

    protected $fillable = [
        "last_checked", "online", "port", "ip_address"
    ];
}
