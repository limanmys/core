<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserSettings extends Model
{
    use UsesUuid;

    protected $fillable = ["server_id", "user_id", "name", "value"];
}
