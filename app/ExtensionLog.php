<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ExtensionLog extends Model
{
    use UsesUuid;

    protected $fillable = [
        "log_id", "title", "message"
    ];
}
