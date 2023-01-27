<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * License
 *
 * @extends Model
 */
class License extends Model
{
    use UsesUuid;

    protected $fillable = ['data', 'extension_id'];
}
