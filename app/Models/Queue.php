<?php

namespace App\Models;

use App\Casts\Jsonb;
use Illuminate\Database\Eloquent\Model;

class Queue extends Model
{
    use UsesUuid;

    protected $table = 'queue';

    protected $fillable = [
        'type',
        'status',
        'data',
        'error',
    ];

    protected $casts = [
        'data' => Jsonb::class,
    ];
}
