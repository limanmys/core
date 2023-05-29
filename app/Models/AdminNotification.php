<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Admin Notification Model
 *
 * @extends Model
 */
class AdminNotification extends Model
{
    use UsesUuid;

    protected $fillable = ['read', 'title', 'message', 'type', 'level', 'mail'];
}
