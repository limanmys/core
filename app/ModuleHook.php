<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ModuleHook extends Model
{
    use UsesUuid;

    protected $fillable = ["module_id", "module_name", "hook", "enabled"];
}
