<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * User Favorites Model
 *
 * @extends Model
 */
class UserFavorites extends Model
{
    use UsesUuid;

    protected $fillable = ['user_id', 'server_id'];
}
