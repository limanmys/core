<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * External Notification Model
 *
 * @extends Model
 */
class ExternalNotification extends Model
{
    use UsesUuid;

    protected $fillable = ['name', 'last_used', 'ip', 'token'];

    protected $hidden = ['token'];
}
