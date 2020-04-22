<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * @method static where(string $string, $name)
 */
class ModuleHook extends Model
{
    use UsesUuid;

    protected $fillable = [
        "module_id", "module_name", "hook", "enabled"
    ];
}
