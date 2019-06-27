<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * @method static where(array $array)
 */
class Widget extends Model
{
    protected $fillable = [
      "title", "type", "server_id", "extension_id", "script_id", "widget_name", "display_type"
    ];
}
