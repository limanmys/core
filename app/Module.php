<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    use UsesUuid;

    protected $fillable = ["name", "enabled"];

    public function hooks()
    {
        return $this->hasMany('App\ModuleHook');
    }
}
