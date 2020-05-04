<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AdminNotification extends Model
{
    use UsesUuid;

    protected $fillable = [
        "read", "title", "message", "type", "level"
    ];
}
