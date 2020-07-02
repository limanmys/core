<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Certificate extends Model
{
    use UsesUuid;

    protected $fillable = ["server_hostname", "origin"];
}
