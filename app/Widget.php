<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class Widget extends Eloquent
{
    protected $collection = 'widgets';
    protected $connection = 'mongodb';
    protected $fillable = [
      "title", "type", "server_id", "extension_id", "script_id", "widget_name", "display_type"
    ];
}
