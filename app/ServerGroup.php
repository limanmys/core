<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ServerGroup extends Model
{
    use UsesUuid;

    protected $fillable = [
        "name", "servers"
    ];
}
