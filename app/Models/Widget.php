<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Widget extends Model
{
    use UsesUuid;

    protected $fillable = [
        "name",
        "text",
        "title",
        "user_id",
        "extension_id",
        "server_id",
        "function",
        "type",
        "order",
    ];
}
