<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * @method static where(string $string, array|\Illuminate\Contracts\Foundation\Application|\Illuminate\Http\Request|string $request)
 */
class Certificate extends Model
{
    use UsesUuid;

    protected $fillable = [
        "server_hostname", "origin"
    ];
}
