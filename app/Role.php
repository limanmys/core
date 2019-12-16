<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use UsesUuid;

    protected $fillable = [
        "name"
    ];

    public function permissions()
    {
        return $this->morphMany('App\Permission', 'morph');
    }

    public function users()
    {
        return $this->belongsToMany('App\User', "role_users");
    }
}
