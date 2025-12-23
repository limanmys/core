<?php

namespace App\Models;

use App\Casts\Jsonb;
use App\Support\Database\CacheQueryBuilder;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class AuditLog extends Model
{
    use UsesUuid;
    use CacheQueryBuilder;

    public $timestamps = ["created_at"];
    const UPDATED_AT = null;

    protected $casts = [
        'details' => Jsonb::class,
        'request' => Jsonb::class,
    ];

    protected $fillable = [
        'id',
        'user_id',
        'ip_address',
        'request',
        'action',
        'type',
        'details',
        'message',
        'created_at'
    ];

    public static function write(
        string $type,
        string $action,
        array $details,
        string $message = "",
        array $extra = [],
        string $user_id = "",
    ) {
        $request = request()->all();
        unset($request['password']);
        unset($request['password_confirmation']);
        unset($request['permissions']);
        unset($request['token']);
        unset($request['token_id']);
        unset($request['script']);

        $request['url'] = request()->url();

        foreach ($request as $k => $v) {
            if ($v === null) {
                unset($request[$k]);
            }
        }

        $request = array_merge($request, $extra);

        return self::create([
            'user_id' => auth('api')->user()?->id ?? $user_id ?? '',
            'ip_address' => request()->ip(),
            'request' => $request,
            'action' => $action,
            'type' => $type,
            'details' => $details,
            'message' => $message
        ]);
    }

    public function user() {
        return $this->belongsTo(User::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::created(function ($auditLog) {
            $logData = [
                'id' => $auditLog->id,
                'user_id' => $auditLog->user_id,
                'ip_address' => $auditLog->ip_address,
                'action' => $auditLog->action,
                'type' => $auditLog->type,
                'message' => $auditLog->message,
                'details' => $auditLog->details,
                'request' => $auditLog->request,
                'created_at' => $auditLog->created_at,
            ];
            
            // Get user info if available
            if ($auditLog->user) {
                $logData['oidc_sub'] = $auditLog->user->oidc_sub ?? '';
            }

            Log::channel('audit')->info($auditLog->message, $logData);
        });
    }
}
