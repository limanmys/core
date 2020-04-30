<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserFavorites extends Model
{
    use UsesUuid;
    protected $fillable = [
        "user_id", "server_id"
    ];
}
