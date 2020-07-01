<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use UsesUuid;

    protected $fillable = ["name"];

    public function permissions()
    {
        return $this->morphMany('App\Models\Permission', 'morph');
    }

    public function users()
    {
        return $this->belongsToMany('App\Models\User', "role_users");
    }
}
