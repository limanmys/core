<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PermissionData extends Model
{
    use UsesUuid;
    protected $fillable = [
        "data"
    ];
}
