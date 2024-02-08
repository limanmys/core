<?php

namespace App\Models;

use App\Casts\Jsonb;
use App\Support\Database\CacheQueryBuilder;
use App\User;
use Illuminate\Database\Eloquent\Model;

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
        array $extra = []
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
            'user_id' => auth('api')->user()->id,
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
}
