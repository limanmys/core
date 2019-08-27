<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AdminNotification extends Model
{
    use UsesUuid;
    protected $fillable = [
        "read"
    ];
}
