<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * LdapRestriction
 *
 * @extends Model
 */
class LdapRestriction extends Model
{
    use UsesUuid;

    protected $fillable = ['name', 'type'];
}
