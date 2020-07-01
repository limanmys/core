<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    use UsesUuid;

    protected $fillable = ["name", "enabled"];
}
