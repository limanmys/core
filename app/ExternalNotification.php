<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ExternalNotification extends Model
{
    use UsesUuid;

    protected $fillable = ["name", "last_used", "ip"];

    protected $hidden = ["token"];
}
