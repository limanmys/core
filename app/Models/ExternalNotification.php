<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExternalNotification extends Model
{
    use UsesUuid;

    protected $fillable = ["name", "last_used", "ip", "token"];

    protected $hidden = ["token"];
}
