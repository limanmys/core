<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

/**
 * @method static where(array $array)
 */
class Widget extends Eloquent
{
    protected $collection = 'widgets';
    protected $connection = 'mongodb';
    protected $fillable = [
      "title", "type", "server_id", "extension_id", "script_id", "widget_name", "display_type"
    ];
}
