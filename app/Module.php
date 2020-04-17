<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    use UsesUuid;

    protected $fillable = [
        "name", "enabled"
    ];
}
