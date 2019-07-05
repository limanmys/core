<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * @method static where(array $array)
 */
class Widget extends Model
{
    use UsesUuid;
    protected $fillable = [
      "name", "text", "title", "user_id", "extension_id", "server_id", "function", "type"
    ];
}
