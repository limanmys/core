<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserExtensionUsageStats extends Model
{
    use UsesUuid;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'extension_id',
        'server_id',
        'usage',
    ];

    public function extension()
    {
        return $this->belongsTo(Extension::class);
    }

    public function server()
    {
        return $this->belongsTo(Server::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
