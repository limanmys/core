<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Oauth2Token extends Model
{
    use HasFactory;

    protected $primaryKey = 'user_id';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'access_token',
        'refresh_token',
        'token_type',
        'expires_in',
        'refresh_expires_in',
    ];

    /**
     * Get the user that owns the Oauth2Token
     *
     * @return BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
