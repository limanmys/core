<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LdapRestriction extends Model
{
    use UsesUuid;

    protected $fillable = ["name", "type"];
}
