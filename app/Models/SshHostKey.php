<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SshHostKey extends Model
{
    use UsesUuid;

    protected $fillable = [
        'host',
        'port',
        'key_type',
        'public_key',
        'fingerprint',
        'approved_by',
        'revoked_by',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'port' => 'integer',
            'revoked_at' => 'datetime',
        ];
    }

    public function scopeActiveForEndpoint(Builder $query, string $host, int $port): Builder
    {
        return $query
            ->where('host', self::normalizeHost($host))
            ->where('port', $port)
            ->whereNull('revoked_at');
    }

    public static function normalizeHost(string $host): string
    {
        $normalized = trim($host, " \t\n\r\0\x0B[]");
        $packed = @inet_pton($normalized);

        if ($packed !== false) {
            return inet_ntop($packed);
        }

        return rtrim(strtolower($normalized), '.');
    }
}
