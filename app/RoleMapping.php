<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RoleMapping extends Model
{
    use UsesUuid;

    protected $fillable = ["role_id", "group_id", "dn"];

    public function role()
    {
        return $this->belongsTo('App\Role');
    }
}
