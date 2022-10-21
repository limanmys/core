<?php

namespace App\Models;

use App\Support\Database\CacheQueryBuilder;
use Illuminate\Database\Eloquent\Model;

class ServerKey extends Model
{
    use UsesUuid, CacheQueryBuilder;

    protected $fillable = ['type', 'data', 'server_id', 'user_id'];
}
