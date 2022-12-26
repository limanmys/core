<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSettings extends Model
{
    use UsesUuid;

    protected $fillable = [
        'key', 'data',
    ];
}
