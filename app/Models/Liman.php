<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Liman extends Model
{
    use UsesUuid;

    protected $table = "limans";

    protected $fillable = [
        "machine_id" ,"last_ip"
    ];
}
