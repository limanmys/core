<?php

namespace App\Models;

use App\Support\Database\CacheQueryBuilder;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class AuthLog extends Model
{
    use UsesUuid, CacheQueryBuilder;

    protected $fillable = [
        'user_id',
        'ip_address',
        'user_agent',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::created(function ($authLog) {
            $logData = [
                'id' => $authLog->id,
                'user_id' => $authLog->user_id,
                'ip_address' => $authLog->ip_address,
                'user_agent' => $authLog->user_agent,
                'created_at' => $authLog->created_at,
            ];

            // Get user info if available
            if ($authLog->user) {
                $logData['user_name'] = $authLog->user->name ?? 'Unknown';
                $logData['user_email'] = $authLog->user->email ?? 'Unknown';
            }

            Log::channel('auth')->info('AUTH_LOG', $logData);
        });
    }
}
