<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class License extends Model
{
    use UsesUuid;

    protected $fillable = ["data", "extension_id"];
}
