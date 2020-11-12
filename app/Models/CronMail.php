<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CronMail extends Model
{
    use UsesUuid;

    protected $fillable = [
        "extension_id", "server_id", "user_id", "type", "target", "last", "to", "cron_type"
    ];

}
