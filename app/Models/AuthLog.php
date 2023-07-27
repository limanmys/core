<?php

namespace App\Models;

use App\Support\Database\CacheQueryBuilder;
use Illuminate\Database\Eloquent\Model;

class AuthLog extends Model
{
    use UsesUuid, CacheQueryBuilder;

    protected $fillable = [
        'user_id',
        'ip_address',
        'user_agent',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
