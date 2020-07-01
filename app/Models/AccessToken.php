<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccessToken extends Model
{
    use UsesUuid;

    protected $fillable = [
        "user_id",
        "token",
        "name",
        "last_used_at",
        "last_used_ip",
    ];
}
