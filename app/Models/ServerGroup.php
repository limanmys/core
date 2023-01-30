<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Server Group Model
 *
 * @extends Model
 */
class ServerGroup extends Model
{
    use UsesUuid;

    protected $fillable = ['name', 'servers'];
}
