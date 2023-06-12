<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Liman
 * This model represents of cluster liman systems connection
 *
 * @extends Model
 */
class Liman extends Model
{
    use UsesUuid;

    protected $table = 'limans';

    protected $fillable = [
        'last_ip',
    ];
}
